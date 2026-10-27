<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Unit\Internal;

use Nowo\AnonymizeBundle\Internal\PrivateDirectory;
use PHPUnit\Framework\TestCase;

/**
 * SEC-004 N1: export / history / stats directories are owner-only, also when created by older versions.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class PrivateDirectoryTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/anonymize_private_dir_' . uniqid();
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->tempDir));
    }

    public function testMissingDirectoryIsCreatedOwnerOnly(): void
    {
        $this->assertTrue(PrivateDirectory::ensure($this->tempDir . '/a/b'));
        $this->assertSame(0o700, fileperms($this->tempDir . '/a/b') & 0o777);
    }

    public function testExistingLooseDirectoryOwnedByTheProcessIsTightened(): void
    {
        mkdir($this->tempDir . '/legacy');
        chmod($this->tempDir . '/legacy', 0o755);

        $this->assertTrue(PrivateDirectory::ensure($this->tempDir . '/legacy'));
        $this->assertSame(0o700, fileperms($this->tempDir . '/legacy') & 0o777);
    }

    public function testUncreatableDirectoryReturnsFalse(): void
    {
        touch($this->tempDir . '/file');

        $this->assertFalse(PrivateDirectory::ensure($this->tempDir . '/file/sub'));
    }

    public function testSymlinkedDirectoryIsNotChangedThroughTheLink(): void
    {
        mkdir($this->tempDir . '/target');
        chmod($this->tempDir . '/target', 0o755);
        symlink($this->tempDir . '/target', $this->tempDir . '/link');

        $this->assertTrue(PrivateDirectory::ensure($this->tempDir . '/link'));
        $this->assertSame(0o755, fileperms($this->tempDir . '/target') & 0o777);
    }
}
