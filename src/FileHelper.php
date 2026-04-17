<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use Tamedevelopers\Support\Capsule\File;
use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Tame;

class FileHelper{
        
    /** Upload Data @var mixed */
    private $data;
        
    /** Regenerated name @var string */
    private $generatedName;

    /**
     * __construct
     *
     * @param  mixed $data
     * @return void
     */
    public function __construct($data = null)
    {
        $this->data = $data;
    }
    
    /**
     * Get File Extension
     *
     * @return string
     */
    public function extension()
    {
        return Str::lower(
            pathinfo($this->data['name'], PATHINFO_EXTENSION)
        );
    }
    
    /**
     * Get Temporary Path
     *
     * @return string|null
     */
    public function tmp()
    {
        return $this->data['tmp_name'];
    }
    
    /**
     * Get File Type
     *
     * @return string
     */
    public function type()
    {
        return $this->data['type'];
    }
    
    /**
     * Get File Name
     *
     * @return string
     */
    public function name()
    {
        return $this->data['name'];
    }
        
    /**
     * Get File MimeType
     *
     * @return string|bool
     * - false if not found
     */
    public function mime()
    {
        return Str::lower(@mime_content_type(
            $this->getTmp()
        ));
    }

    /**
     * Get File Size
     * 
     * @return int
     */
    public function size()
    {
        return $this->data['size'];
    }

    /**
     * Get File Size Formatted (Human Readable)
     * 
     * @param int $precision Decimal places
     * @return string
     * Examples: "1.5 KB", "2.3 MB", "1.2 GB"
     */
    public function sizeFormatted(int $precision = 2): string
    {
        $size = $this->data['size'];
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $size >= 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, $precision) . '' . strtolower($units[$i]);
    }

    /**
     * Get Size in Specific Unit
     * 
     * @param string $unit 'B', 'KB', 'MB', 'GB'
     * @return float
     */
    public function sizeIn(string $unit = 'KB'): float
    {
        $size = (float)$this->data['size'];
        $units = ['B' => 1, 'KB' => 1024, 'MB' => 1048576, 'GB' => 1073741824];
        
        return $size / ($units[strtoupper($unit)] ?? 1);
    }

    /**
     * Check if file size is within limit
     * 
     * @param string|int|float $maxSize Maximum size in bytes
     * @param string|int|float $minSize Minimum size in bytes (optional)
     * @return bool
     */
    public function sizeBetween($maxSize, $minSize = null): bool
    {
        $size = $this->data['size'];

        $maxSize = Tame::sizeToBytes($maxSize);
        $minSize = Tame::sizeToBytes($minSize);
        
        if ($minSize !== null && $size < $minSize) {
            return false;
        }
        
        return $size <= $maxSize;
    }

    /**
     * Get Image Size
     * 
     * @return array
     * [width, height]
     */
    public function imageSize()
    {
        // get image attributes
        $imagePath = @getimagesize($this->getTmp());

        return [
            'width'  => $imagePath[0] ?? null,
            'height' => $imagePath[1] ?? null
        ];
    }

    /**
     * Determine whether the uploaded file is a valid image.
     *
     * This method validates the file by:
     * - Checking that a MIME type exists
     * - Ensuring the MIME type starts with "image/"
     * - Verifying the file can be read by getimagesize()
     *
     * This helps prevent spoofed extensions (e.g. renamed .exe to .jpg).
     *
     * @return bool
     *  True if the file is a valid image, otherwise false.
     */
    public function isImage()
    {
        $mime = $this->mime();

        return $mime && str_starts_with($mime, 'image/') 
            && @getimagesize($this->getTmp()) !== false;
    }
       
    /**
     * Generate new file
     *
     * @param  bool $allow
     * @return string
     * - Generated Filename
     */
    public function generate(?bool $allow = true)
    {
        // if not allowed
        if(!$allow){
            $name = $this->name();
        }

        $name = bin2hex(random_bytes(25)) . '.' . $this->extension();

        $this->generatedName = $name;

        return $name;
    }

    /**
     * Move uploaded file to destination
     * 
     * @param string $destination Destination path
     * @param string|null $newName New filename (optional)
     * @return array
     */
    public function move(string $destination, ?string $newName = null)
    {
        $fileName   = File::name($newName ?? $this->generatedName ?? $this->name());
        $directory  = Tame::stringReplacer($destination);
        $fullPath   = "{$directory}/{$fileName}.{$this->extension()}";

        if(!File::isDirectory($directory)){
            File::makeDirectory($directory);
        }

        $move = @move_uploaded_file($this->tmp(), $fullPath);

        return [
            'path' => $fullPath,
            'name' => $fileName,
            'status' => $move,
        ];
    }

    /**
     * Get Upload Error Message
     * 
     * @return string|null
     */
    public function getError(): ?string
    {
        $error = $this->data['error'] ?? UPLOAD_ERR_OK;
        
        $errors = [
            UPLOAD_ERR_OK => 'No error',
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension',
        ];
        
        return $errors[$error] ?? ($error === UPLOAD_ERR_OK ? null : 'Unknown error');
    }

    /**
     * Delete temporary file (cleanup)
     * 
     * @return bool
     */
    public function cleanup(): bool
    {
        $tmpPath = $this->getTmp();
        if ($tmpPath && file_exists($tmpPath)) {
            return unlink($tmpPath);
        }
        return false;
    }

    /**
     * Get Temporary Path or null
     *
     * @return string|null
     */
    private function getTmp()
    {
        return $this->tmp() ?: 'null';
    }

}