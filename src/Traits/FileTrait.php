<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Traits;

use Closure;
use Tamedevelopers\Support\FileHelper;

/**
 * @property mixed $name file name
 * @property mixed $collections files collections
 */
trait FileTrait
{
    /**
     * Get a specific file from the collection
     * 
     * @param string|null $fileName The form field name
     * @return static
     */
    public static function collect(?string $fileName = null)
    {
        self::$name = $fileName;

        if (is_array($fileName) || !isset($_FILES[$fileName])) {
            return new static([]);
        }

        $files = $_FILES[$fileName] ?? [];

        return new static(
            self::normalizeFiles($files)
        );
    }

    /**
     * Get all files from the collections
     */
    public function all(): array
    {
        if(!empty(self::$collections)){
            return self::$collections;
        }

        $files  = $_FILES;
        $collect = [];

        foreach ($files as $key => $file) {
            // Single PHP Upload Array Structure (e.g., $_FILES['avatar'])
            $isSingle = isset($file['name'], $file['tmp_name'], $file['error']) && !is_array($file['name']);

            // Multi-Upload PHP File Structure (e.g., $_FILES['document'])
            $isMultiple = isset($file['name'], $file['tmp_name'], $file['error']) && is_array($file['name']);

            if ($isSingle) {
                $collect[$key] = new FileHelper(self::createFileItem($file));
            } elseif ($isMultiple) {
                $collect[$key] = [];
                foreach ($file['name'] as $index => $name) {
                    $collect[$key][] = new FileHelper(self::createFileItem($file, $index));
                }
            }
        }

        // Build local items array if a name key exists
        $localItems = [];
        if (! empty(self::$name) && ! empty($this->collection)) {
            $localItems[self::$name] = $this->collection;
        }

        // Check if local collection has data for the active key
        $hasLocalData = ! empty($localItems[self::$name] ?? null);

        if ($hasLocalData) {
            // Local is NOT empty -> Use local values to fill in matching keys / fallbacks
            self::$collections = array_merge($collect, $localItems);
        } else {
            // Local IS empty -> Global $_FILES overrides/precedes local
            self::$collections = array_merge($localItems, $collect);
        }

        return self::$collections;
    }

    /**
     * Get all files from the collection
     */
    public function get(): array
    {
        return $this->collection;
    }

    /**
     * Get the first file from the collection
     * 
     * @return null|\Tamedevelopers\Support\FileHelper
     */
    public function first()
    {
        return $this->get()[0] ?? null;
    }

    /**
     * Get the last file from the collection
     * 
     * @return array|null
     */
    public function last()
    {
        return !empty($this->collection) ? end($this->collection) : null;
    }

    /**
     * Check if a file is set in the $_FILES superglobal
     *
     * @param string|null $name
     */
    public function isset($name = null): bool
    {
        $getName = empty($name) ? self::$name : $name;

        return isset($_FILES[$getName]);
    }

    /**
     * Check if collection is empty
     */
    public function isEmpty(): bool
    {
        return empty($this->collection);
    }

    /**
     * Check if collection is not empty
     */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * Get the number of files in collection
     */
    public function count(): int
    {
        return count($this->collection);
    }

    /**
     * Get summary statistics for files collection
     * 
     * @return array Summary data
     */
    public function summary(): array
    {
        if ($this->isEmpty()) {
            return [
                'total_files' => 0,
                'total_size' => 0,
                'total_size_mb' => 0,
                'file_types' => [],
                'has_errors' => false
            ];
        }

        $totalSize = 0;
        $fileTypes = [];
        $hasErrors = false;

        foreach ($this->collection as $file) {
            $totalSize += $file->size();
            $fileTypes[$file->type()] = ($fileTypes[$file->type()] ?? 0) + 1;
            
            if ($file->error() !== UPLOAD_ERR_OK) {
                $hasErrors = true;
            }
        }

        return [
            'total_files' => $this->count(),
            'total_size' => $totalSize,
            'total_size_mb' => round($totalSize / (1024 * 1024), 2),
            'file_types' => $fileTypes,
            'has_errors' => $hasErrors
        ];
    }

    /**
     * Loop through each file in the collection
     * 
     * @return $this
     */
    public function each(Closure $callback)
    {
        foreach ($this->collection as $index => $file) {
            if ($callback($file, $index) === false) {
                break;
            }
        }

        return $this;
    }

    /**
     * Filter files in the collection
     * 
     * @return $this
     */
    public function filter(Closure $callback)
    {
        $filtered = array_filter($this->collection, $callback);

        return new static(array_values($filtered));
    }

    /**
     * Get only valid files (without upload errors)
     * 
     * @return $this
     */
    public function valid()
    {
        return $this->filter(function($file) {
            return $file->error() === UPLOAD_ERR_OK;
        });
    }

    /**
     * Publish JavaScript code to automatically convert file inputs to support multiple files
     * Call this method and echo the output in your HTML head or before file inputs
     * 
     * @return string JavaScript code
     */
    public static function publishJS(): string
    {
      return <<<'JS'
        <script>
            'use strict';
            
            window.initMultiInputFile = function() {
                var inputs = document.querySelectorAll('input[type="file"]');
                
                for (var i = 0; i < inputs.length; i++) {
                    var input = inputs[i];
                    
                    if ((input.multiple && input.name) && input.name.indexOf('[]') === -1) {
                        input.name = input.name + '[]';
                    }
                }
            };

            document.addEventListener('DOMContentLoaded', function(){
                window.initMultiInputFile();
            });
        </script>
      JS;
    }

    /**
     * Normalize and collect file items into helper instances.
     *
     * @param array<string, mixed> $files Raw file payload (single or multi-upload structure)
     * @return array
     */
    private static function normalizeFiles($files)
    {
        $collect = [];

        // Single PHP Upload Array Structure (e.g., $_FILES['avatar'])
        $isSingle = isset($files['name'], $files['tmp_name'], $files['error']) && !is_array($files['name']);

        // Multi-Upload PHP File Structure (e.g., $_FILES['document'] with files[])
        $isMultiple = isset($files['name'], $files['tmp_name'], $files['error']) && is_array($files['name']);

        if ($isSingle) {
            $collect[] = new FileHelper(self::createFileItem($files));
        } elseif ($isMultiple) {
            foreach ($files['name'] as $index => $name) {
                $collect[] = new FileHelper(self::createFileItem($files, $index));
            }
        } else {
            // Nested/Multi-Field Structure (e.g., passing raw $_FILES containing both 'avatar' and 'document')
            foreach ($files as $key => $file) {
                if (is_array($file)) {
                    $collect = array_merge($collect, self::normalizeFiles($file));
                }
            }
        }

        return $collect;
    }

    /**
     * Create standardized file item structure
     * 
     * @param array $files Files data from $_FILES
     * @param int|null $index Array index for multiple files
     * @return array Structured file data
     */
    private static function createFileItem(array $files, ?int $index = null): array
    {
        $isArray = $index !== null;

        return [
            'name' => $isArray ? $files['name'][$index] : $files['name'],
            'path' => $isArray ? ($files['full_path'][$index] ?? $files['name'][$index]) : ($files['full_path'] ?? $files['name']),
            'filename' => pathinfo($isArray ? $files['name'][$index] : $files['name'], PATHINFO_FILENAME),
            'type' => $isArray ? $files['type'][$index] : $files['type'],
            'tmp_name' => $isArray ? $files['tmp_name'][$index] : $files['tmp_name'],
            'error' => $isArray ? $files['error'][$index] : $files['error'],
            'size' => $isArray ? $files['size'][$index] : $files['size'],
            'extension' => pathinfo($isArray ? $files['name'][$index] : $files['name'], PATHINFO_EXTENSION),
        ];
    }
    
}