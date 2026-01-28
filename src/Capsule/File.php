<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Capsule;

use Tamedevelopers\Support\Tame;

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
        // Handle URLs explicitly
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return self::getFromUrl($path);
        }

        // Prevent PHP from trying to open a missing file
        if (!is_file($path)) {
            return '';
        }

        // Safe read
        return @file_get_contents($path) ?: '';
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
        return file_put_contents($path, $contents, $flags);
    }

    /**
     * Delete the file at the given path.
     *
     * @param string $file (Relative|Absolute Path)
     * @param string|null $restrictedfileName
     * @return bool
     */
    public static function delete(string $file, $restrictedfileName = null): bool
    {
        return Tame::unlink($file, $restrictedfileName);
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
        // Check if path is a URL
        $isUrl = filter_var($path, FILTER_VALIDATE_URL);

        [$extension, $mime] = [
            pathinfo($path, PATHINFO_EXTENSION),
            !$isUrl && self::isFile($path) ? (@mime_content_type($path) ?: false) : false
        ];
        
        // if mime is not found, try to guess it from the extension
        if ($mime === false) {
            $mimes = [
                // ====== IMAGES ======
                'jpg'   => 'image/jpeg',
                'jpeg'  => 'image/jpeg',
                'jpe'   => 'image/jpeg',
                'png'   => 'image/png',
                'gif'   => 'image/gif',
                'bmp'   => 'image/bmp',
                'webp'  => 'image/webp',
                'avif'  => 'image/avif',
                'heif'  => 'image/heif',
                'heic'  => 'image/heic',
                'svg'   => 'image/svg+xml',
                'svgz'  => 'image/svg+xml',
                'ico'   => 'image/x-icon',
                'cur'   => 'image/x-icon',
                'tif'   => 'image/tiff',
                'tiff'  => 'image/tiff',
                'psd'   => 'image/vnd.adobe.photoshop',
                'ai'    => 'application/postscript',
                'eps'   => 'application/postscript',
                'pdf'   => 'application/pdf',
                'apng'  => 'image/apng',
                'jxr'   => 'image/jxr',
                'wdp'   => 'image/vnd.ms-photo',
                'exr'   => 'image/x-exr',
                'hdr'   => 'image/vnd.radiance',

                // ====== DOCUMENTS ======
                'txt'   => 'text/plain',
                'csv'   => 'text/csv',
                'tsv'   => 'text/tab-separated-values',
                'log'   => 'text/plain',
                'rtf'   => 'application/rtf',
                'doc'   => 'application/msword',
                'dot'   => 'application/msword',
                'docx'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'dotx'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
                'odt'   => 'application/vnd.oasis.opendocument.text',
                'ott'   => 'application/vnd.oasis.opendocument.text-template',
                'xls'   => 'application/vnd.ms-excel',
                'xlt'   => 'application/vnd.ms-excel',
                'xlsx'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'xltx'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
                'ods'   => 'application/vnd.oasis.opendocument.spreadsheet',
                'odp'   => 'application/vnd.oasis.opendocument.presentation',
                'ppt'   => 'application/vnd.ms-powerpoint',
                'pptx'  => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'ppsx'  => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
                'potx'  => 'application/vnd.openxmlformats-officedocument.presentationml.template',
                'xml'   => 'application/xml',
                'html'  => 'text/html',
                'htm'   => 'text/html',
                'json'  => 'application/json',
                'yaml'  => 'application/x-yaml',
                'yml'   => 'application/x-yaml',
                'md'    => 'text/markdown',

                // ====== ARCHIVES ======
                'zip'   => 'application/zip',
                'rar'   => 'application/x-rar-compressed',
                'tar'   => 'application/x-tar',
                'gz'    => 'application/gzip',
                'tgz'   => 'application/gzip',
                'bz2'   => 'application/x-bzip2',
                '7z'    => 'application/x-7z-compressed',
                'xz'    => 'application/x-xz',
                'iso'   => 'application/x-iso9660-image',

                // ====== AUDIO ======
                'mp3'   => 'audio/mpeg',
                'wav'   => 'audio/wav',
                'ogg'   => 'audio/ogg',
                'oga'   => 'audio/ogg',
                'flac'  => 'audio/flac',
                'aac'   => 'audio/aac',
                'm4a'   => 'audio/mp4',
                'opus'  => 'audio/opus',
                'mid'   => 'audio/midi',
                'midi'  => 'audio/midi',

                // ====== VIDEO ======
                'mp4'   => 'video/mp4',
                'm4v'   => 'video/x-m4v',
                'mov'   => 'video/quicktime',
                'wmv'   => 'video/x-ms-wmv',
                'avi'   => 'video/x-msvideo',
                'flv'   => 'video/x-flv',
                'webm'  => 'video/webm',
                'mkv'   => 'video/x-matroska',
                '3gp'   => 'video/3gpp',
                '3g2'   => 'video/3gpp2',

                // ====== CODE / WEB ======
                'php'   => 'application/x-httpd-php',
                'js'    => 'application/javascript',
                'mjs'   => 'application/javascript',
                'jsx'   => 'text/jsx',
                'ts'    => 'application/typescript',
                'tsx'   => 'text/tsx',
                'css'   => 'text/css',
                'scss'  => 'text/x-scss',
                'less'  => 'text/x-less',
                'csv'   => 'text/csv',
                'conf'  => 'text/plain',
                'ini'   => 'text/plain',
                'env'   => 'text/plain',

                // ====== FONTS ======
                'woff'  => 'font/woff',
                'woff2' => 'font/woff2',
                'ttf'   => 'font/ttf',
                'otf'   => 'font/otf',
                'eot'   => 'application/vnd.ms-fontobject',

                // ====== EXECUTABLES / BINARIES ======
                'exe'   => 'application/x-msdownload',
                'dll'   => 'application/x-msdownload',
                'bin'   => 'application/octet-stream',
                'dat'   => 'application/octet-stream',
                'msi'   => 'application/x-msi',
                'apk'   => 'application/vnd.android.package-archive',
                'deb'   => 'application/vnd.debian.binary-package',
                'rpm'   => 'application/x-rpm',
                'dmg'   => 'application/x-apple-diskimage',

                // ====== TEXT / SCRIPTING ======
                'sh'    => 'application/x-sh',
                'bat'   => 'application/x-msdos-program',
                'cmd'   => 'application/cmd',
                'py'    => 'text/x-python',
                'rb'    => 'text/x-ruby',
                'go'    => 'text/x-go',
                'java'  => 'text/x-java-source',
                'c'     => 'text/x-c',
                'cpp'   => 'text/x-c++',
                'h'     => 'text/x-c',
                'cs'    => 'text/x-csharp',

                // ====== OTHER ======
                'bak'   => 'application/octet-stream',
                'lock'  => 'text/plain',
                'sql'   => 'application/sql',
                'db'    => 'application/x-sqlite3',
                'sqlite'=> 'application/x-sqlite3',
            ];

            $mime = $mimes[$extension] ?? false;
        }

        return $mime;
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
        return @is_readable($path);
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

    /**
     * Fetch remote content safely via HTTP(S).
     *
     * @param string $url
     * @return string|false
     */
    private static function getFromUrl(string $url): string|false
    {
        // Basic hardening
        if (!in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
            return false;
        }

        $context = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'timeout' => 10,
                'header'  => "User-Agent: Tamedevelopers-Support/1.0\r\n",
            ],
        ]);

        $result = @file_get_contents($url, false, $context);

        return $result !== false ? $result : '';
    }

}
