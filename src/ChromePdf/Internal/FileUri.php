<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\ChromePdf\Internal;

/**
 * Builds file:// URIs for local font paths and HTML temp files.
 */
final class FileUri
{
    public static function fromPath(string $path): string
    {
        $resolved = realpath($path);
        if ($resolved === false) {
            throw new \InvalidArgumentException(sprintf('Path could not be resolved: %s', $path));
        }

        $normalized = str_replace('\\', '/', $resolved);

        if (preg_match('#^[A-Za-z]:/#', $normalized) === 1) {
            return 'file:///' . $normalized;
        }

        return 'file://' . $normalized;
    }
}
