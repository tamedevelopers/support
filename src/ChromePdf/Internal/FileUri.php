<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\ChromePdf\Internal;

use Tamedevelopers\Support\Server;
use Tamedevelopers\Support\Str;

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


    public static function fromDomain(string $path): string
    {
        $storage = Str::replace(Server::getServers('server'), '', $path);

        return Server::formatWithDomainURI($storage);
    }

}
