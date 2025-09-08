<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use FilesystemIterator;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Tamedevelopers\Support\Capsule\File;
use Tamedevelopers\Support\Traits\ServerTrait;


/**
 * Improved autoloader and file loader.
 *
 * Differences from AutoloadRegister:
 * - Skips dot entries during directory scan (better perf, fewer edge cases)
 * - Supports class, trait, interface, and enum (PHP 8.1+)
 * - Idempotent autoload registration (won't re-register repeatedly)
 * - Same public API and usage: AutoloadRegister2::load('dir') or ::load(['dir1','dir2'])
 */
class AutoloadRegister
{
    use ServerTrait;

    /**
     * Directories to scan
     * @var array<string>
     */
    private static array $baseDirectories = [];

    /**
     * FQN class => file path
     * @var array<string,string>
     */
    private static array $classMap = [];

    /**
     * relative php file (no class/trait/interface/enum) => file path
     * @var array<string,string>
     */
    private static array $fileMap = [];

    /**
     * Ensure we only register spl_autoload once.
     */
    private static bool $registered = false;

    /**
     * Autoload function to load classes and files in the given folder(s).
     *
     * @param string|array $baseDirectory Directory path(s) relative to application base.
     * - Do not include the root path. e.g. 'classes' or 'app/main'
     */
    public static function load(string|array $baseDirectory): void
    {
        self::$baseDirectories = [];

        $dirs = is_array($baseDirectory) ? $baseDirectory : [$baseDirectory];
        foreach ($dirs as $directory) {
            $path = self::formatWithBaseDirectory($directory);
            if (File::isDirectory($path)) {
                self::$baseDirectories[] = $path;
            }
        }

        if (empty(self::$baseDirectories)) {
            return; // nothing to do
        }

        self::boot();
    }

    /**
     * Boot the autoloader by scanning directories and registering autoload.
     */
    private static function boot(): void
    {
        // reset maps to avoid duplicates across repeated calls
        self::$classMap = [];
        self::$fileMap  = [];

        foreach (self::$baseDirectories as $base) {
            self::generateClassMapFor($base);
            self::generateFileMapFor($base);
        }

        self::loadFiles();

        if (!self::$registered) {
            spl_autoload_register([__CLASS__, 'loadClass']);
            self::$registered = true;
        }
    }

    /**
     * PSR-like autoload callback using the internally built class map.
     */
    private static function loadClass(string $className): void
    {
        $className = ltrim($className, '\\');
        $filePath = self::$classMap[$className] ?? null;
        if ($filePath && File::exists($filePath)) {
            require_once $filePath;
        }
    }

    /**
     * Load standalone files (without class/trait/interface/enum) from the file map.
     */
    private static function loadFiles(): void
    {
        foreach (self::$fileMap as $filePath) {
            if (File::exists($filePath)) {
                require_once $filePath;
            }
        }
    }

    /**
     * Build class map for a single base directory.
     */
    private static function generateClassMapFor(string $base): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $filePath  = $file->getPathname();
                $className = self::getClassName($filePath);
                if ($className !== null) {
                    self::$classMap[ltrim($className, '\\')] = self::pathReplacer($filePath);
                }
            }
        }
    }

    /**
     * Build file map (php files without class/trait/interface/enum) for a base directory.
     */
    private static function generateFileMapFor(string $base): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $filePath  = $file->getPathname();
                $className = self::getClassName($filePath);
                if ($className === null) {
                    $relativePath = self::getRelativePath($base, $filePath);
                    self::$fileMap[$relativePath] = self::pathReplacer($filePath);
                }
            }
        }
    }

    /**
     * Get the relative path for a file with respect to the provided base directory.
     */
    private static function getRelativePath(string $base, string $filePath): string
    {
        $relativePath = substr($filePath, strlen($base));
        return ltrim($relativePath, '/\\');
    }

    /**
     * Parse a PHP file and return the fully-qualified class/trait/interface/enum name, or null.
     */
    private static function getClassName(string $filePath): ?string
    {
        $namespace = '';
        $content   = File::get($filePath);
        if ($content === '' || $content === null) {
            return null;
        }

        $tokens = token_get_all($content);
        $count  = count($tokens);

        // Target tokens to detect named declarations
        $targets = [T_CLASS, T_TRAIT, T_INTERFACE];
        if (defined('T_ENUM')) {
            $targets[] = T_ENUM; // PHP 8.1+
        }

        for ($i = 0; $i < $count; $i++) {
            // Namespace collection
            if (is_array($tokens[$i]) && $tokens[$i][0] === T_NAMESPACE) {
                $namespace = '';
                for ($j = $i + 1; $j < $count; $j++) {
                    if (is_array($tokens[$j]) && ($tokens[$j][0] === T_STRING || $tokens[$j][0] === T_NS_SEPARATOR)) {
                        $namespace .= $tokens[$j][1];
                    } elseif ($tokens[$j] === '{' || $tokens[$j] === ';') {
                        break;
                    }
                }
            }

            // Class/Trait/Interface/Enum name collection
            if (is_array($tokens[$i]) && in_array($tokens[$i][0], $targets, true)) {
                // Skip anonymous class: "class(" pattern (no T_STRING name)
                for ($j = $i + 1; $j < $count; $j++) {
                    if ($tokens[$j] === '{' || $tokens[$j] === '(') {
                        // '{' for regular bodies, '(' likely anonymous class
                        break;
                    } elseif (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                        $name = $tokens[$j][1];
                        return ltrim(($namespace !== '' ? $namespace . '\\' : '') . $name, '\\');
                    }
                }
            }
        }

        return null;
    }
    
}