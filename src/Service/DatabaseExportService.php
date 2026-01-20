<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Service;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;

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
     * @param ContainerInterface $container The service container
     * @param string $outputDir The output directory for exports
     * @param string $filenamePattern The filename pattern
     * @param string $compression The compression format (none, gzip, bzip2, zip)
     * @param bool $autoGitignore Whether to automatically update .gitignore
     */
    public function __construct(
        private ContainerInterface $container,
        private string $outputDir,
        private string $filenamePattern,
        private string $compression,
        private bool $autoGitignore
    ) {}

    /**
     * Exports a database connection.
     *
     * @param EntityManagerInterface $em The entity manager
     * @param string $connectionName The connection name
     * @return string|null The path to the exported file, or null on failure
     */
    public function exportConnection(EntityManagerInterface $em, string $connectionName): ?string
    {
        $connection = $em->getConnection();
        $driver = $connection->getDriver()->getName();
        $database = $connection->getDatabase();

        // Generate filename
        $filename = $this->generateFilename($connectionName, $database, $driver);
        $outputPath = rtrim($this->outputDir, '/') . '/' . $filename;

        // Ensure output directory exists
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }

        // Export based on driver
        $exportedFile = match ($driver) {
            'pdo_mysql' => $this->exportMySQL($connection, $outputPath),
            'pdo_pgsql' => $this->exportPostgreSQL($connection, $outputPath),
            'pdo_sqlite' => $this->exportSQLite($connection, $outputPath),
            default => null,
        };

        if ($exportedFile === null) {
            return null;
        }

        // Apply compression if needed
        if ($this->compression !== 'none' && file_exists($exportedFile)) {
            $compressedFile = $this->compressFile($exportedFile);
            if ($compressedFile !== null) {
                // Remove uncompressed file if compression succeeded
                unlink($exportedFile);
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
     * @param string $host The MongoDB host
     * @param int $port The MongoDB port
     * @return string|null The path to the exported file, or null on failure
     */
    public function exportMongoDB(string $connectionName, string $database, string $host = 'localhost', int $port = 27017): ?string
    {
        // Check if mongodump is available
        if (!$this->commandExists('mongodump')) {
            return null;
        }

        // Generate filename
        $filename = $this->generateFilename($connectionName, $database, 'mongodb');
        $outputPath = rtrim($this->outputDir, '/') . '/' . $filename;

        // Ensure output directory exists
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }

        // Export MongoDB
        $command = sprintf(
            'mongodump --host %s --port %d --db %s --out %s 2>&1',
            escapeshellarg($host),
            $port,
            escapeshellarg($database),
            escapeshellarg($this->outputDir)
        );

        exec($command, $output, $returnCode);

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
     * @return string|null The path to the exported file, or null on failure
     */
    private function exportMySQL(Connection $connection, string $outputPath): ?string
    {
        if (!$this->commandExists('mysqldump')) {
            return null;
        }

        $params = $connection->getParams();
        $host = $params['host'] ?? 'localhost';
        $port = $params['port'] ?? 3306;
        $user = $params['user'] ?? 'root';
        $password = $params['password'] ?? '';
        $database = $connection->getDatabase();

        $command = sprintf(
            'mysqldump --host=%s --port=%d --user=%s %s %s > %s 2>&1',
            escapeshellarg($host),
            $port,
            escapeshellarg($user),
            $password ? '--password=' . escapeshellarg($password) : '',
            escapeshellarg($database),
            escapeshellarg($outputPath)
        );

        exec($command, $output, $returnCode);

        return $returnCode === 0 && file_exists($outputPath) ? $outputPath : null;
    }

    /**
     * Exports a PostgreSQL database.
     *
     * @param Connection $connection The database connection
     * @param string $outputPath The output file path
     * @return string|null The path to the exported file, or null on failure
     */
    private function exportPostgreSQL(Connection $connection, string $outputPath): ?string
    {
        if (!$this->commandExists('pg_dump')) {
            return null;
        }

        $params = $connection->getParams();
        $host = $params['host'] ?? 'localhost';
        $port = $params['port'] ?? 5432;
        $user = $params['user'] ?? 'postgres';
        $password = $params['password'] ?? '';
        $database = $connection->getDatabase();

        // Set PGPASSWORD environment variable
        $env = $password ? 'PGPASSWORD=' . escapeshellarg($password) . ' ' : '';

        $command = sprintf(
            '%spg_dump --host=%s --port=%d --username=%s --dbname=%s --file=%s --no-password 2>&1',
            $env,
            escapeshellarg($host),
            $port,
            escapeshellarg($user),
            escapeshellarg($database),
            escapeshellarg($outputPath)
        );

        exec($command, $output, $returnCode);

        return $returnCode === 0 && file_exists($outputPath) ? $outputPath : null;
    }

    /**
     * Exports a SQLite database.
     *
     * @param Connection $connection The database connection
     * @param string $outputPath The output file path
     * @return string|null The path to the exported file, or null on failure
     */
    private function exportSQLite(Connection $connection, string $outputPath): ?string
    {
        $params = $connection->getParams();
        $path = $params['path'] ?? null;

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
     * @return string The generated filename
     */
    private function generateFilename(string $connectionName, string $database, string $driver): string
    {
        $date = date('Y-m-d');
        $time = date('H-i-s');
        $format = $this->getFileExtension($driver);

        $filename = $this->filenamePattern;
        $filename = str_replace('{connection}', $connectionName, $filename);
        $filename = str_replace('{database}', $database, $filename);
        $filename = str_replace('{date}', $date, $filename);
        $filename = str_replace('{time}', $time, $filename);
        $filename = str_replace('{format}', $format, $filename);

        return $filename;
    }

    /**
     * Gets the file extension for a driver.
     *
     * @param string $driver The driver name
     * @return string The file extension
     */
    private function getFileExtension(string $driver): string
    {
        return match ($driver) {
            'pdo_mysql' => 'sql',
            'pdo_pgsql' => 'sql',
            'pdo_sqlite' => 'sqlite',
            'mongodb' => 'bson',
            default => 'sql',
        };
    }

    /**
     * Compresses a file.
     *
     * @param string $filePath The file path to compress
     * @return string|null The path to the compressed file, or null on failure
     */
    private function compressFile(string $filePath): ?string
    {
        $compressedPath = $filePath;

        switch ($this->compression) {
            case 'gzip':
                if ($this->commandExists('gzip')) {
                    exec('gzip ' . escapeshellarg($filePath) . ' 2>&1', $output, $returnCode);
                    $compressedPath = $filePath . '.gz';
                }
                break;

            case 'bzip2':
                if ($this->commandExists('bzip2')) {
                    exec('bzip2 ' . escapeshellarg($filePath) . ' 2>&1', $output, $returnCode);
                    $compressedPath = $filePath . '.bz2';
                }
                break;

            case 'zip':
                if (class_exists(\ZipArchive::class)) {
                    $zipPath = $filePath . '.zip';
                    $zip = new \ZipArchive();
                    if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
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
     * @return bool True on success, false on failure
     */
    private function createZipArchive(string $directory, string $zipPath): bool
    {
        if (!class_exists(\ZipArchive::class)) {
            return false;
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return false;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($directory) + 1);
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
     * @return bool True on success, false on failure
     */
    private function createTarArchive(string $directory, string $tarPath): bool
    {
        if (!$this->commandExists('tar')) {
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
            escapeshellarg($directory)
        );

        exec($command, $output, $returnCode);

        return $returnCode === 0 && file_exists($tarPath);
    }

    /**
     * Removes a directory recursively.
     *
     * @param string $directory The directory to remove
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
     * Checks if a command exists.
     *
     * @param string $command The command name
     * @return bool True if the command exists, false otherwise
     */
    private function commandExists(string $command): bool
    {
        $whereIsCommand = (PHP_OS === 'WINNT') ? 'where' : 'which';
        $process = proc_open(
            "$whereIsCommand $command",
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes
        );

        if ($process === false) {
            return false;
        }

        $stdout = stream_get_contents($pipes[1]);
        $returnCode = proc_close($process);

        return $returnCode === 0 && !empty($stdout);
    }

    /**
     * Updates the .gitignore file to exclude the export directory.
     *
     * @return void
     */
    private function updateGitignore(): void
    {
        // Get project directory
        if (!$this->container->has('kernel')) {
            return;
        }

        $kernel = $this->container->get('kernel');
        $projectDir = $kernel->getProjectDir();
        $gitignorePath = $projectDir . '/.gitignore';

        // Calculate relative path from project root
        $relativeExportDir = str_replace($projectDir . '/', '', $this->outputDir);
        $gitignoreEntry = $relativeExportDir . '/';

        // Read existing .gitignore
        $content = file_exists($gitignorePath) ? file_get_contents($gitignorePath) : '';

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
