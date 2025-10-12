<?php



class FilesCollection
{
    protected $collection = [];

    /**
     * Constructor of class.
     * 
     * @param array|null $collection
     * @return void
     */
    public function __construct($collection = null) 
    {
        if(!empty($collection)){
            $this->collection = $collection;
        }
    }

    /**
     * Get all files from the collection
     */
    public function get()
    {
        return $this->all();
    }

    /**
     * Get all files from the collection
     */
    public function all()
    {
        return $this->collection;
    }

    /**
     * Get the first file from the collection
     */
    public function first()
    {
        return $this->collection[0] ?? null;
    }

    /**
     * Get the last file from the collection
     */
    public function last()
    {
        return !empty($this->collection) ? end($this->collection) : null;
    }

    /**
     * Check if collection is empty
     */
    public function isEmpty()
    {
        return empty($this->collection);
    }

    /**
     * Get the number of files in collection
     */
    public function count()
    {
        return count($this->collection);
    }

    /**
     * Get a specific file from the collection
     * 
     * @param string $fileName The form field name
     * @return static
     */
    public static function file($fileName): static
    {
        if (!isset($_FILES[$fileName])) {
            return new static([]);
        }

        $files = $_FILES[$fileName];
        $collect = [];

        // Handle multiple files (works with both name="files" and name="files[]")
        if (is_array($files['name'])) {
            foreach ($files['name'] as $index => $name) {
                $collect[] = self::createFileItem($files, $index);
            }
        }
        // Handle single file
        else {
            $collect[] = self::createFileItem($files);
        }

        return new static($collect);
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
            $totalSize += $file['size'];
            $fileTypes[$file['type']] = ($fileTypes[$file['type']] ?? 0) + 1;
            
            if ($file['error'] !== UPLOAD_ERR_OK) {
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
     */
    public function each(callable $callback)
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
     */
    public function filter(callable $callback)
    {
        $filtered = array_filter($this->collection, $callback);
        return new static(array_values($filtered));
    }

    /**
     * Get only valid files (without upload errors)
     */
    public function valid()
    {
        return $this->filter(function($file) {
            return $file['error'] === UPLOAD_ERR_OK;
        });
    }

    /**
     * publish JavaScript code to automatically convert file inputs to support multiple files
     * Call this method and echo the output in your HTML head or before file inputs
     * 
     * @return string JavaScript code
     */
    public static function publishJS(): string
    {
      return <<<'JS'
        <script>
        (function() {
            'use strict';
            
            function initMultiInputFile() {
                var inputs = document.querySelectorAll('input[type="file"]');
                
                for (var i = 0; i < inputs.length; i++) {
                    var input = inputs[i];
                    
                    // Add brackets to name if not already present
                    if ((input.multiple && input.name) && input.name.indexOf('[]') === -1) {
                        input.name = input.name + '[]';
                    }
                }
            }

            document.addEventListener('DOMContentLoaded', function(){
              initMultiInputFile();
            });
        })();
        </script>
      JS;
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