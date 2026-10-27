<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Service;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Nowo\AnonymizeBundle\Helper\DbalHelper;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use ZipArchive;

use function strlen;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use const PATHINFO_FILENAME;

/**
 * Service for exporting databases.
 *
 * Supports MySQL, PostgreSQL, SQLite, and MongoDB exports with optional compression.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
final class DatabaseExportService
{
    /**
     * Creates a new DatabaseExportService instance.
     *
     * @param string $projectDir The kernel project directory
     * @param string $outputDir The output directory for exports
     * @param string $filenamePattern The filename pattern
     * @param string $compression The compression format (none, gzip, bzip2, zip)
     * @param bool $autoGitignore Whether to automatically update .gitignore
     * @param CommandRunnerInterface|null $commandRunner Optional command runner abstraction
     * @param float $timeoutSeconds Subprocess timeout for dumps/compression (REQ-RUNTIME-001)
     */
    public function __construct(
        private readonly string $projectDir,
        private readonly string $outputDir,
        private readonly string $filenamePattern,
        private readonly string $compression,
        private readonly bool $autoGitignore,
        private ?CommandRunnerInterface $commandRunner = null,
        float $timeoutSeconds = 180.0,
    ) {
        $this->commandRunner ??= new SystemCommandRunner(null, $timeoutSeconds);
    }

    /**
     * Exports a database connection.
     *
     * @param EntityManagerInterface $em The entity manager
     * @param string $connectionName The connection name
     *
     * @return string|null The path to the exported file, or null on failure
     */
    public function exportConnection(EntityManagerInterface $em, string $connectionName): ?string
    {
        // Dumps hold personal data: the directory is 0700 and every file is created 0600 before a tool writes it
        $connection = $em->getConnection();
        $driver     = DbalHelper::getDriverName($connection);
        $database   = $connection->getDatabase();

        // Generate filename
        $filename   = $this->generateFilename($connectionName, (string) $database, $driver);
        $outputPath = rtrim($this->outputDir, '/') . '/' . $filename;

        $this->ensureOutputDirectory();

        // Export based on driver
        $exportedFile = match ($driver) {
            'pdo_mysql'  => $this->exportMySQL($connection, $outputPath),
            'pdo_pgsql'  => $this->exportPostgreSQL($connection, $outputPath),
            'pdo_sqlite' => $this->exportSQLite($connection, $outputPath),
            default      => null,
        };

        if ($exportedFile === null) {
            return null;
        }

        // Apply compression if needed
        if ($this->compression !== 'none' && file_exists($exportedFile)) {
            $compressedFile = $this->compressFile($exportedFile);
            if ($compressedFile !== null) {
                // gzip/bzip2 remove the source file; zip leaves it — delete only if still present
                if (is_file($exportedFile)) {
                    unlink($exportedFile);
                }
                $exportedFile = $compressedFile;
            }
        }

        chmod($exportedFile, 0o600);

        // Update .gitignore if enabled
        if ($this->autoGitignore) {
            $this->updateGitignore();
        }

        return $exportedFile;
    }

    /**
     * Exports a MongoDB database.
     *
     * @param string $connectionName The connection name
     * @param string $database The database name
     * @param string $host The MongoDB host (ignored when $uri is given)
     * @param int $port The MongoDB port (ignored when $uri is given)
     * @param string|null $uri Full connection string (e.g. MONGODB_URL, with credentials / replica set / options).
     *                         Passed to mongodump through a 0600 --config file, never on the command line.
     *
     * @return string|null The path to the exported file, or null on failure
     */
    public function exportMongoDB(string $connectionName, string $database, string $host = 'localhost', int $port = 27017, ?string $uri = null): ?string
    {
        // Check if mongodump is available; the database name is also a directory name (no paths)
        if (!$this->getCommandRunner()->commandExists('mongodump') || preg_match('/^[A-Za-z0-9_-]{1,63}$/', $database) !== 1) {
            return null;
        }

        // Generate filename
        $filename = $this->generateFilename($connectionName, $database, 'mongodb');

        $this->ensureOutputDirectory();

        // Export MongoDB. With a connection string, credentials go through a 0600 --config file
        // (mongodump >= 100.3.0) so they are not visible in the process list.
        $configFile = null;
        if ($uri !== null && $uri !== '') {
            $configFile = $this->writeSecretFile('mongodump_', 'uri: ' . $this->yamlString($uri) . "\n");
            $argv       = ['mongodump', '--config=' . $configFile, '--db', $database, '--out', $this->outputDir];
        } else {
            $argv = ['mongodump', '--host', $host, '--port', (string) $port, '--db', $database, '--out', $this->outputDir];
        }

        // mongodump writes plain BSON files to <outputDir>/<database>: always removed, also on failure
        $mongoExportDir = $this->outputDir . '/' . $database;
        $archivePath    = $this->outputDir . '/' . pathinfo($filename, PATHINFO_FILENAME);

        try {
            if (!is_dir($mongoExportDir)) {
                mkdir($mongoExportDir, 0o700);
            }
            try {
                $returnCode = $this->runCommand($argv);
            } finally {
                $this->removeSecretFile($configFile);
            }

            // The directory was pre-created: an empty one means mongodump exported nothing
            if ($returnCode !== 0 || array_diff((array) scandir($mongoExportDir), ['.', '..']) === []) {
                return null;
            }

            if ($this->compression === 'zip') {
                $archivePath .= '.zip';
                $archived = $this->createZipArchive($mongoExportDir, $archivePath);
            } else {
                // Use tar with compression
                $ext = $this->compression === 'gzip' ? 'tar.gz' : ($this->compression === 'bzip2' ? 'tar.bz2' : 'tar');
                $archivePath .= '.' . $ext;
                $this->createPrivateFile($archivePath);
                $archived = $this->createTarArchive($mongoExportDir, $archivePath);
            }
        } finally {
            $this->removeDirectory($mongoExportDir);
        }

        if (!$archived || !is_file($archivePath)) {
            // Never keep a partial archive around
            $this->removeFile($archivePath);

            return null;
        }
        chmod($archivePath, 0o600);

        // Update .gitignore if enabled
        if ($this->autoGitignore) {
            $this->updateGitignore();
        }

        return $archivePath;
    }

    /**
     * Exports a MySQL database.
     *
     * @param Connection $connection The database connection
     * @param string $outputPath The output file path
     *
     * @return string|null The path to the exported file, or null on failure
     */
    private function exportMySQL(Connection $connection, string $outputPath): ?string
    {
        if (!$this->getCommandRunner()->commandExists('mysqldump')) {
            return null;
        }

        $params   = $connection->getParams();
        $host     = $params['host'] ?? 'localhost';
        $port     = $params['port'] ?? 3306;
        $user     = $params['user'] ?? 'root';
        $password = (string) ($params['password'] ?? '');
        $database = $connection->getDatabase();

        // The password goes through a 0600 option file (never on the command line, where `ps` shows it).
        // --result-file writes only the dump; mysqldump warnings/errors stay on stderr (runner output).
        $optionsFile = null;
        if ($password !== '') {
            $optionsFile = $this->writeSecretFile('mysqldump_', "[client]\npassword=\"" . $this->escapeMysqlOptionValue($password) . "\"\n");
        }

        $argv = ['mysqldump'];
        if ($optionsFile !== null) {
            // --defaults-extra-file must be the first option
            $argv[] = '--defaults-extra-file=' . $optionsFile;
        }
        $argv[] = '--host=' . $host;
        $argv[] = '--port=' . $port;
        $argv[] = '--user=' . $user;
        $argv[] = '--result-file=' . $outputPath;
        $argv[] = (string) $database;

        $this->createPrivateFile($outputPath);
        try {
            $returnCode = $this->runCommand($argv);
        } finally {
            $this->removeSecretFile($optionsFile);
        }

        return $this->keepDumpOnSuccess($returnCode, $outputPath);
    }

    /**
     * Exports a PostgreSQL database.
     *
     * @param Connection $connection The database connection
     * @param string $outputPath The output file path
     *
     * @return string|null The path to the exported file, or null on failure
     */
    private function exportPostgreSQL(Connection $connection, string $outputPath): ?string
    {
        if (!$this->getCommandRunner()->commandExists('pg_dump')) {
            return null;
        }

        $params   = $connection->getParams();
        $host     = $params['host'] ?? 'localhost';
        $port     = $params['port'] ?? 5432;
        $user     = $params['user'] ?? 'postgres';
        $password = (string) ($params['password'] ?? '');
        $database = $connection->getDatabase();

        // The password goes through a 0600 password file (PGPASSFILE): an inline PGPASSWORD=... would be
        // part of the shell command line and visible in the process list.
        $passFile = null;
        if ($password !== '') {
            $passFile = $this->writeSecretFile('pgpass_', '*:*:*:*:' . $this->escapePgpassValue($password) . "\n");
        }

        $argv = ['pg_dump', '--host=' . $host, '--port=' . $port, '--username=' . $user, '--dbname=' . $database, '--file=' . $outputPath, '--no-password'];

        $this->createPrivateFile($outputPath);
        try {
            $returnCode = $this->runCommand($argv, $passFile !== null ? ['PGPASSFILE' => $passFile] : []);
        } finally {
            $this->removeSecretFile($passFile);
        }

        return $this->keepDumpOnSuccess($returnCode, $outputPath);
    }

    /**
     * Exports a SQLite database.
     *
     * @param Connection $connection The database connection
     * @param string $outputPath The output file path
     *
     * @return string|null The path to the exported file, or null on failure
     */
    private function exportSQLite(Connection $connection, string $outputPath): ?string
    {
        $params = $connection->getParams();
        $path   = $params['path'] ?? null;

        if ($path === null || !file_exists($path)) {
            return null;
        }

        // SQLite is just a file copy (copy() keeps the 0600 mode of the existing target)
        $this->createPrivateFile($outputPath);
        if (copy($path, $outputPath)) {
            return $outputPath;
        }

        return null;
    }

    /**
     * Generates a filename based on the pattern.
     *
     * @param string $connectionName The connection name
     * @param string $database The database name
     * @param string $driver The driver name
     *
     * @return string The generated filename
     */
    private function generateFilename(string $connectionName, string $database, string $driver): string
    {
        $date   = date('Y-m-d');
        $time   = date('H-i-s');
        $format = $this->getFileExtension($driver);

        $filename = $this->filenamePattern;
        $filename = str_replace('{connection}', $connectionName, $filename);
        $filename = str_replace('{database}', $database, $filename);
        $filename = str_replace('{date}', $date, $filename);
        $filename = str_replace('{time}', $time, $filename);

        return str_replace('{format}', $format, $filename);
    }

    /**
     * Gets the file extension for a driver.
     *
     * @param string $driver The driver name
     *
     * @return string The file extension
     */
    private function getFileExtension(string $driver): string
    {
        return match ($driver) {
            'pdo_mysql'  => 'sql',
            'pdo_pgsql'  => 'sql',
            'pdo_sqlite' => 'sqlite',
            'mongodb'    => 'bson',
            default      => 'sql',
        };
    }

    /**
     * Compresses a file.
     *
     * @param string $filePath The file path to compress
     *
     * @return string|null The path to the compressed file, or null on failure
     */
    private function compressFile(string $filePath): ?string
    {
        // Stays null when the tool / extension is missing: the caller then keeps the plain dump
        $compressedPath = null;

        switch ($this->compression) {
            case 'gzip':
            case 'bzip2':
                $tool = $this->compression;
                if ($this->getCommandRunner()->commandExists($tool)) {
                    $compressedPath = $filePath . ($tool === 'gzip' ? '.gz' : '.bz2');
                    if ($this->runCommand([$tool, $filePath]) !== 0) {
                        // Partial archive removed; the caller keeps the plain dump
                        $this->removeFile($compressedPath);

                        return null;
                    }
                }
                break;

            case 'zip':
                if (class_exists(ZipArchive::class)) {
                    $zipPath = $filePath . '.zip';
                    $zip     = new ZipArchive();
                    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                        $zip->addFile($filePath, basename($filePath));
                        $zip->close();
                        $compressedPath = $zipPath;
                    }
                }
                break;
        }

        return $compressedPath !== null && file_exists($compressedPath) ? $compressedPath : null;
    }

    /**
     * Creates a ZIP archive from a directory.
     *
     * @param string $directory The directory to archive
     * @param string $zipPath The output ZIP file path
     *
     * @return bool True on success, false on failure
     */
    private function createZipArchive(string $directory, string $zipPath): bool
    {
        // Without ext-zip (misconfigured hosts) the archive is simply not created
        $zip = class_exists(ZipArchive::class) ? new ZipArchive() : null;
        if (!$zip instanceof ZipArchive || $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return false;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory),
            RecursiveIteratorIterator::LEAVES_ONLY,
        );

        foreach ($iterator as $file) {
            if (!$file->isDir()) {
                $filePath     = $file->getRealPath();
                $relativePath = substr((string) $filePath, strlen($directory) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }

        return $zip->close();
    }

    /**
     * Creates a TAR archive from a directory.
     *
     * @param string $directory The directory to archive
     * @param string $tarPath The output TAR file path
     *
     * @return bool True on success, false on failure
     */
    private function createTarArchive(string $directory, string $tarPath): bool
    {
        if (!$this->getCommandRunner()->commandExists('tar')) {
            return false;
        }

        $compressionFlag = '';
        if (str_ends_with($tarPath, '.gz')) {
            $compressionFlag = 'z';
        } elseif (str_ends_with($tarPath, '.bz2')) {
            $compressionFlag = 'j';
        }

        $returnCode = $this->runCommand(['tar', '-c' . $compressionFlag . 'f', $tarPath, '-C', $directory, '.']);

        return $returnCode === 0 && file_exists($tarPath);
    }

    /**
     * Removes a directory recursively.
     *
     * @param string $directory The directory to remove
     *
     * @return bool True on success, false on failure
     */
    private function removeDirectory(string $directory): bool
    {
        if (is_link($directory) || !is_dir($directory)) {
            // A symlink is removed itself, never followed
            return $this->removeFile($directory);
        }

        foreach (array_diff((array) scandir($directory), ['.', '..']) as $file) {
            $path = $directory . '/' . $file;
            if (is_dir($path) && !is_link($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        return rmdir($directory);
    }

    /**
     * Removes a file or symlink when present.
     */
    private function removeFile(string $path): bool
    {
        return (is_file($path) || is_link($path)) && unlink($path);
    }

    /**
     * Keeps the dump only when the tool succeeded; a failed or timed-out run never leaves a partial dump behind.
     */
    private function keepDumpOnSuccess(int $returnCode, string $outputPath): ?string
    {
        // The file was pre-created empty: an empty file means the tool wrote nothing
        if ($returnCode !== 0 || filesize($outputPath) === 0) {
            $this->removeFile($outputPath);

            return null;
        }

        return $outputPath;
    }

    /**
     * Creates the output directory owner-only when missing.
     */
    private function ensureOutputDirectory(): void
    {
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0o700, true);
        }
    }

    /**
     * Creates (or truncates) a file with mode 0600 before a tool writes into it: tools opening it with O_TRUNC
     * keep that mode, so the dump is never readable by other users, not even while it is being written.
     */
    private function createPrivateFile(string $path): void
    {
        touch($path);
        chmod($path, 0o600);
    }

    /**
     * Runs an external tool: as an argument vector (no shell) when the runner supports it, otherwise as an
     * escaped command string for plain CommandRunnerInterface implementations.
     *
     * @param list<string> $argv
     * @param array<string, string> $env
     */
    private function runCommand(array $argv, array $env = []): int
    {
        $runner = $this->getCommandRunner();
        if ($runner instanceof ArgvCommandRunnerInterface) {
            return $runner->run($argv, $env, $output);
        }

        $parts = [];
        foreach ($env as $name => $value) {
            $parts[] = $name . '=' . escapeshellarg($value);
        }
        foreach ($argv as $index => $argument) {
            $parts[] = $this->shellArgument($argument, $index === 0);
        }

        return $runner->exec(implode(' ', $parts), $output);
    }

    /**
     * Shell form of one argument: program names, bare options and integers as-is, "--option=value" with the
     * value quoted, anything else quoted.
     */
    private function shellArgument(string $argument, bool $isProgram): string
    {
        if ($isProgram || preg_match('/^(?:-[A-Za-z-]+|\d+)$/', $argument) === 1) {
            return $argument;
        }
        if (preg_match('/^(--[A-Za-z-]+=)(.*)$/s', $argument, $matches) === 1) {
            return $matches[1] . (ctype_digit($matches[2]) ? $matches[2] : escapeshellarg($matches[2]));
        }

        return escapeshellarg($argument);
    }

    /**
     * Writes credentials to a new owner-only (0600) temporary file and returns its path.
     *
     * @throws RuntimeException when the file cannot be created
     */
    private function writeSecretFile(string $prefix, string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'nowo_anonymize_' . $prefix) ?: throw new RuntimeException('Unable to create a temporary credentials file.');

        // tempnam() creates the file 0600; enforce it before writing the secret.
        chmod($path, 0o600);
        file_put_contents($path, $content);

        return $path;
    }

    private function removeSecretFile(?string $path): void
    {
        if ($path !== null && is_file($path)) {
            unlink($path);
        }
    }

    /**
     * Escapes a value for a double-quoted MySQL option file entry (backslash escape sequences).
     *
     * Quotes are escaped too: the option-file parser tracks unescaped quotes to find "#" comments, so a
     * bare quote inside the value would end the quoted string and truncate the password at a later "#".
     */
    private function escapeMysqlOptionValue(string $value): string
    {
        return strtr($value, ['\\' => '\\\\', '"' => '\\"', "'" => "\\'", "\n" => '\\n', "\r" => '\\r', "\t" => '\\t']);
    }

    /**
     * Escapes a field of a libpq password file (":" and "\" must be backslash-escaped).
     */
    private function escapePgpassValue(string $value): string
    {
        return strtr($value, ['\\' => '\\\\', ':' => '\\:']);
    }

    /**
     * Encodes a string as a YAML double-quoted scalar (a JSON string is valid YAML).
     */
    private function yamlString(string $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function getCommandRunner(): CommandRunnerInterface
    {
        return $this->commandRunner ?? throw new LogicException('Command runner is not initialized.');
    }

    /**
     * Updates the .gitignore file to exclude the export directory.
     */
    private function updateGitignore(): void
    {
        $projectDir = $this->projectDir;

        if ($projectDir === '') {
            return;
        }

        $gitignorePath = $projectDir . '/.gitignore';

        // Calculate relative path from project root
        $relativeExportDir = str_replace($projectDir . '/', '', $this->outputDir);
        $gitignoreEntry    = $relativeExportDir . '/';

        // Read existing .gitignore
        $content = file_exists($gitignorePath) ? (file_get_contents($gitignorePath) ?: '') : '';

        // Check if entry already exists
        if (str_contains($content, $gitignoreEntry)) {
            return;
        }

        // Add entry
        $content .= "\n# Database exports (auto-generated by AnonymizeBundle)\n";
        $content .= $gitignoreEntry . "\n";

        file_put_contents($gitignorePath, $content);
    }
}
