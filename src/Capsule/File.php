<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Capsule;

/**
 * Class File
 *
 * Provides file handling utilities similar to Laravel's Filesystem.
 * Supports reading, writing, deleting, copying, moving, and checking files.
 */
class File {

    /**
     * Get all files in a directory as SplFileInfo objects.
     *
     * @param string $directory
     * @return array<int, \SplFileInfo>
     */
    public static function files(string $directory): array
    {
        $result = [];
        if (!self::isDirectory($directory)) {
            return $result;
        }

        $iterator = new \FilesystemIterator($directory, \FilesystemIterator::SKIP_DOTS);
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $result[] = $file;
            }
        }

        return $result;
    }

    /**
     * Create a directory.
     *
     * @param string $path
     * @param int $mode
     * @param bool $recursive
     * @return bool
     */
    public static function makeDirectory(string $path, int $mode = 0777, bool $recursive = true): bool
    {
        if (self::isDirectory($path)) {
            return true;
        }

        return mkdir($path, $mode, $recursive);
    }

    /**
     * Validate a file name for unsafe characters.
     *
     * @param string $fileName
     * @param bool $disallowUnsafeCharacters
     * @return bool
     */
    public static function isValidName(string $fileName, bool $disallowUnsafeCharacters = true): bool
    {
        if ($disallowUnsafeCharacters) {
            // Disallow: / \ ? % * : | " < >
            return !preg_match('/[\\\/\?%\*:|"<>]/', $fileName);
        }
        return true;
    }

    /**
     * Determine if a file exists at a given path.
     *
     * @param string $path
     * @return bool
     */
    public static function exists(string $path): bool
    {
        return is_file($path);
    }

    /**
     * Get the contents of a file.
     *
     * @param string $path
     * @return string|false
     */
    public static function get(string $path): string|false
    {
        return @file_get_contents($path);
    }

    /**
     * Write the contents to a file.
     *
     * @param string $path
     * @param string $contents
     * @param int $flags
     * @return bool|int
     */
    public static function put(string $path, string $contents, int $flags = 0): bool|int
    {
        return @file_put_contents($path, $contents, $flags);
    }

    /**
     * Delete the file at the given path.
     *
     * @param string $path
     * @return bool
     */
    public static function delete(string $path): bool
    {
        return self::exists($path) ? unlink($path) : false;
    }

    /**
     * Copy a file to a new location.
     *
     * @param string $from
     * @param string $to
     * @return bool
     */
    public static function copy(string $from, string $to): bool
    {
        return copy($from, $to);
    }

    /**
     * Move a file to a new location.
     *
     * @param string $from
     * @param string $to
     * @return bool
     */
    public static function move(string $from, string $to): bool
    {
        return rename($from, $to);
    }

    /**
     * Get the file size.
     *
     * @param string $path
     * @return int|false
     */
    public static function size(string $path): int|false
    {
        return filesize($path);
    }

    /**
     * Get the file's last modification time.
     *
     * @param string $path
     * @return int|false
     */
    public static function lastModified(string $path): int|false
    {
        return filemtime($path);
    }

    /**
     * Get the file's extension.
     *
     * @param string $path
     * @return string|null
     */
    public static function extension(string $path): ?string
    {
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        return $ext !== '' ? $ext : null;
    }

    /**
     * Get the file's name.
     *
     * @param string $path
     * @return string
     */
    public static function name(string $path): string
    {
        return pathinfo($path, PATHINFO_FILENAME);
    }

    /**
     * Get the file's basename.
     *
     * @param string $path
     * @return string
     */
    public static function base(string $path): string
    {
        return basename($path);
    }

    /**
     * Get the file's mime type.
     *
     * @param string $path
     * @return string|false
     */
    public static function mimeType(string $path): string|false
    {
        return mime_content_type($path);
    }

    /**
     * Get the file's type (file, dir, link, etc).
     *
     * @param string $path
     * @return string|false
     */
    public static function type(string $path): string|false
    {
        return filetype($path);
    }

    /**
     * Get the file's permissions.
     *
     * @param string $path
     * @return int|false
     */
    public static function permissions(string $path): int|false
    {
        return fileperms($path);
    }

    /**
     * Check if the file is readable.
     *
     * @param string $path
     * @return bool
     */
    public static function isReadable(string $path): bool
    {
        return is_readable($path);
    }

    /**
     * Check if the file is writable.
     *
     * @param string $path
     * @return bool
     */
    public static function isWritable(string $path): bool
    {
        return is_writable($path);
    }

    /**
     * Check if the file is a directory.
     *
     * @param string $path
     * @return bool
     */
    public static function isDirectory(string $path): bool
    {
        return is_dir($path);
    }

    /**
     * Check if the file is a regular file.
     *
     * @param string $path
     * @return bool
     */
    public static function isFile(string $path): bool
    {
        return is_file($path);
    }

    /**
     * Determines if the given string represents a valid file type.
     *
     * @param string $string The string to check for file type validity.
     * @return bool Returns true if the string is a valid file type, false otherwise.
     */
    public static function isFileType(?string $string = null) 
    {
        return pathinfo($string, PATHINFO_EXTENSION) !== '';
    }

}
