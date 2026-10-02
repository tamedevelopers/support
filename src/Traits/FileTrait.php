<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Traits;

use Closure;
use Tamedevelopers\Support\Capsule\File;
use Tamedevelopers\Support\FileHelper;
use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Tame;

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
     * Publish JavaScript code to handle multiple file inputs
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
     * Publish JavaScript code to validate file size before upload with a modern UI notification
     * 
     * @param string|null $customCssClass Optional custom CSS class to style the popup alert
     * @param int $durationMs Auto-close duration in milliseconds (default: 4000ms / 4s)
     * @return string
     */
    public static function publishMaxSizeJS($customCssClass = null, $durationMs = 2000)
    {
        $size           = self::getServerMaxUploadSize();
        $maxBytes       = $size['size'];
        $maxSizeFormat  = $size['format'];
        $cssClass       = $customCssClass ? Str::trim($customCssClass) : '';
        $styleContent   = self::getToastCss();

        return <<<JS
        <style>
            {$styleContent};
        </style>
        <script>
            'use strict';
            
            window.showTameFileToast = function(title, message) {
                var container = document.querySelector('.tame-file-bag-toast-container');
                if (!container) {
                    container = document.createElement('div');
                    container.className = 'tame-file-bag-toast-container';
                    document.body.appendChild(container);
                }

                var toast = document.createElement('div');
                toast.className = 'tame-file-bag-toast {$cssClass}';
                
                toast.innerHTML = 
                    '<div class="tame-file-bag-toast-content">' +
                        '<div class="tame-file-bag-toast-title">' + title + '</div>' +
                        '<div class="tame-file-bag-toast-message">' + message + '</div>' +
                    '</div>' +
                    '<button type="button" class="tame-file-bag-toast-close" onclick="this.parentElement.classList.remove(\'show\'); setTimeout(function(){ this.parentElement.remove(); }.bind(this), 250);">&times;</button>';

                container.appendChild(toast);

                // Trigger animation
                setTimeout(function() {
                    toast.classList.add('show');
                }, 10);

                // Auto remove after specified duration
                setTimeout(function() {
                    if (toast && toast.parentElement) {
                        toast.classList.remove('show');
                        setTimeout(function() {
                            if (toast.parentElement) {
                                toast.remove();
                            }
                        }, 250);
                    }
                }, {$durationMs});
            };

            window.initMaxSizeInputFile = function() {
                var inputs = document.querySelectorAll('input[type="file"]');
                var limit  = {$maxBytes};
                
                for (var i = 0; i < inputs.length; i++) {
                    var input = inputs[i];
                    
                    if (!input.dataset.maxSizeBound) {
                        input.dataset.maxSizeBound = 'true';

                        if (input.form && !input.form.dataset.maxSizeSubmitBound) {
                            input.form.dataset.maxSizeSubmitBound = 'true';
                            
                            input.form.addEventListener('submit', function(e) {
                                var formInputs = e.target.querySelectorAll('input[type="file"]');
                                
                                for (var k = 0; k < formInputs.length; k++) {
                                    var formFiles = formInputs[k].files;
                                    for (var m = 0; m < formFiles.length; m++) {
                                        if (formFiles[m].size > limit) {
                                            window.showTameFileToast(
                                                '413 Payload Too Large', 
                                                '"' + formFiles[m].name + '" exceeds the maximum allowed upload limit of {$maxSizeFormat}.'
                                            );
                                            e.preventDefault();
                                            e.stopPropagation();
                                            return false;
                                        }
                                    }
                                }
                            });
                        }
                    }
                }
            };

            document.addEventListener('DOMContentLoaded', function(){
                window.initMaxSizeInputFile();
            });
        </script>
        JS;
    }

    /**
     * Get the server's maximum upload file size in bytes.
     *
     * @return array{size: int, format: string}
     */
    public static function getServerMaxUploadSize()
    {
        // Retrieve server configuration values
        $uploadMax = Tame::sizeToBytes(ini_get('upload_max_filesize'));
        $postMax   = Tame::sizeToBytes(ini_get('post_max_size'));
        $memory    = Tame::sizeToBytes(ini_get('memory_limit'));

        // Filter out disabled or unlimited memory (-1)
        $limits = array_filter([$uploadMax, $postMax, $memory], fn($size) => $size > 0);

        // The true limit is the lowest among all configurations
        $maxSize = empty($limits) ? 0 : min($limits);

        return [
            'size' => $maxSize,
            'format' => Tame::byteToUnit($maxSize),
        ];
    }

    /**
     * Get Toast Css
     */
    private static function getToastCss(): string
    {
        $themeFileName = __DIR__ . '/../Capsule/Dummy/toast.css';
        $content = File::get(Tame::stringReplacer($themeFileName));

        return Str::minifyCss($content);
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