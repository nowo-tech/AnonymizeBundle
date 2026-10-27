<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Internal;

use function function_exists;

/**
 * Owner-only (0700) directories for exports, run history and statistics.
 *
 * Creates the directory 0700 when missing, and tightens an existing one (e.g. created 0755 by bundle
 * versions up to 1.2) when the current process owns it. Directories owned by someone else are left alone.
 *
 * @internal This class is for bundle use only. Do not rely on it in application code.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class PrivateDirectory
{
    /**
     * Ensures $directory exists and is owner-only when owned by this process; returns whether it is a directory.
     */
    public static function ensure(string $directory): bool
    {
        if (!is_dir($directory)) {
            // Failure (e.g. a parent path is a file) is reported by the caller through the return value
            @mkdir($directory, 0o700, true);
        }
        if (!is_dir($directory) || is_link($directory)) {
            return is_dir($directory);
        }

        $processUid = function_exists('posix_geteuid') ? posix_geteuid() : getmyuid();
        if (fileowner($directory) === $processUid && (fileperms($directory) & 0o077) !== 0) {
            chmod($directory, 0o700);
            clearstatcache(true, $directory);
        }

        return true;
    }
}
