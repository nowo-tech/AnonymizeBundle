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

use function sprintf;
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
        $connection = $em->getConnection();
        $driver     = DbalHelper::getDriverName($connection);
        $database   = $connection->getDatabase();

        // Generate filename
        $filename   = $this->generateFilename($connectionName, (string) $database, $driver);
        $outputPath = rtrim($this->outputDir, '/') . '/' . $filename;

        // Ensure output directory exists
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0o755, true);
        }

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
        // Check if mongodump is available
        if (!$this->getCommandRunner()->commandExists('mongodump')) {
            return null;
        }

        // Generate filename
        $filename = $this->generateFilename($connectionName, $database, 'mongodb');

        // Ensure output directory exists
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0o755, true);
        }

        // Export MongoDB. With a connection string, credentials go through a 0600 --config file
        // (mongodump >= 100.3.0) so they are not visible in the process list.
        $configFile = null;
        if ($uri !== null && $uri !== '') {
            $configFile = $this->writeSecretFile('mongodump_', 'uri: ' . $this->yamlString($uri) . "\n");
            $command    = sprintf(
                'mongodump --config=%s --db %s --out %s',
                escapeshellarg($configFile),
                escapeshellarg($database),
                escapeshellarg($this->outputDir),
            );
        } else {
            $command = sprintf(
                'mongodump --host %s --port %d --db %s --out %s',
                escapeshellarg($host),
                $port,
                escapeshellarg($database),
                escapeshellarg($this->outputDir),
            );
        }

        try {
            $returnCode = $this->getCommandRunner()->exec($command, $output);
        } finally {
            $this->removeSecretFile($configFile);
        }

        if ($returnCode !== 0) {
            return null;
        }

        // MongoDB exports to a directory, we need to create an archive
        $mongoExportDir = $this->outputDir . '/' . $database;
        if (!is_dir($mongoExportDir)) {
            return null;
        }

        // Create archive
        $archivePath = $this->outputDir . '/' . pathinfo($filename, PATHINFO_FILENAME);
        if ($this->compression === 'zip') {
            $archivePath .= '.zip';
            $this->createZipArchive($mongoExportDir, $archivePath);
        } else {
            // Use tar with compression
            $ext = $this->compression === 'gzip' ? 'tar.gz' : ($this->compression === 'bzip2' ? 'tar.bz2' : 'tar');
            $archivePath .= '.' . $ext;
            $this->createTarArchive($mongoExportDir, $archivePath);
        }

        // Remove temporary directory
        $this->removeDirectory($mongoExportDir);

        // Update .gitignore if enabled
        if ($this->autoGitignore) {
            $this->updateGitignore();
        }

        return file_exists($archivePath) ? $archivePath : null;
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

        $command = sprintf(
            'mysqldump %s--host=%s --port=%d --user=%s --result-file=%s %s',
            $optionsFile !== null ? '--defaults-extra-file=' . escapeshellarg($optionsFile) . ' ' : '',
            escapeshellarg((string) $host),
            $port,
            escapeshellarg((string) $user),
            escapeshellarg($outputPath),
            escapeshellarg((string) $database),
        );

        try {
            $returnCode = $this->getCommandRunner()->exec($command, $output);
        } finally {
            $this->removeSecretFile($optionsFile);
        }

        if ($returnCode !== 0) {
            // Never keep a partial dump around.
            if (is_file($outputPath)) {
                unlink($outputPath);
            }

            return null;
        }

        return file_exists($outputPath) ? $outputPath : null;
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

        $command = sprintf(
            '%spg_dump --host=%s --port=%d --username=%s --dbname=%s --file=%s --no-password',
            $passFile !== null ? 'PGPASSFILE=' . escapeshellarg($passFile) . ' ' : '',
            escapeshellarg((string) $host),
            $port,
            escapeshellarg((string) $user),
            escapeshellarg((string) $database),
            escapeshellarg($outputPath),
        );

        try {
            $returnCode = $this->getCommandRunner()->exec($command, $output);
        } finally {
            $this->removeSecretFile($passFile);
        }

        return $returnCode === 0 && file_exists($outputPath) ? $outputPath : null;
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

        // SQLite is just a file copy
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
        $compressedPath = $filePath;

        switch ($this->compression) {
            case 'gzip':
                if ($this->getCommandRunner()->commandExists('gzip')) {
                    $this->getCommandRunner()->exec('gzip ' . escapeshellarg($filePath) . ' 2>&1', $output);
                    $compressedPath = $filePath . '.gz';
                }
                break;

            case 'bzip2':
                if ($this->getCommandRunner()->commandExists('bzip2')) {
                    $this->getCommandRunner()->exec('bzip2 ' . escapeshellarg($filePath) . ' 2>&1', $output);
                    $compressedPath = $filePath . '.bz2';
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

        return file_exists($compressedPath) ? $compressedPath : null;
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
        if ($zip === null || $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
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

        $command = sprintf(
            'tar -c%sf %s -C %s . 2>&1',
            $compressionFlag,
            escapeshellarg($tarPath),
            escapeshellarg($directory),
        );

        $returnCode = $this->getCommandRunner()->exec($command, $output);

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
        if (!is_dir($directory)) {
            return false;
        }

        $files = array_diff(scandir($directory), ['.', '..']);
        foreach ($files as $file) {
            $path = $directory . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        return rmdir($directory);
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
