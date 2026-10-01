<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use Tamedevelopers\Support\Capsule\File;
use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Tame;

class FileHelper{

    private string $name;
    private string $path;
    private string $filename;
    private string $type;
    private string $tmp_name;
    private int $error;
    private int $size;
    private string $extension;
    private string $errorMessage;
    private string|null $generatedName;

    /**
     * __construct
     *
     * @param  mixed $data
     * @return void
     */
    public function __construct($data = null)
    {
        $this->name         = $data['name'] ?? '';
        $this->path         = $data['path'] ?? '';
        $this->filename     = $data['filename'] ?? '';
        $this->type         = $data['type'] ?? '';
        $this->tmp_name     = $data['tmp_name'] ?? '';
        $this->error        = $data['error'] ?? 0;
        $this->size         = $data['size'] ?? 0;
        $this->extension    = $data['extension'] ?? '';
        $this->errorMessage = $this->errorMessage();
    }
    
    /**
     * Get File Extension
     */
    public function extension(): string
    {
        return Str::lower($this->extension);
    }

    /**
     * Get File Extension with dot (.jpg)
     */
    public function extensionWithDot(): string
    {
        return ".{$this->extension()}";
    }
    
    /**
     * Error code
     */
    public function error(): int
    {
        return $this->error;
    }
    
    /**
     * Error on upload
     */
    public function noError(): bool
    {
        return ($this->error() === UPLOAD_ERR_OK);
    }

    /**
     * Get file path
     */
    public function path(): string|null
    {
        return $this->path;
    }
    
    /**
     * Get Temporary Path
     */
    public function tmp(): string|null
    {
        return $this->tmp_name;
    }
    
    /**
     * Get File Type
     *
     * @return string
     */
    public function type(): string
    {
        return $this->type;
    }
    
    /**
     * If uploaded file is empty
     */
    public function isEmpty(): bool
    {
        return empty($this->name);
    }
    
    /**
     * If uploaded file is not empty
     */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }
    
    /**
     * Get File Name
     */
    public function name(): string
    {
        return $this->name;
    }
    
    /**
     * Get File Name
     */
    public function fileName(): string
    {
        return $this->filename;
    }
        
    /**
     * Get File MimeType
     */
    public function mime(): string|bool
    {
        return Str::lower(@mime_content_type(
            $this->getTmp()
        ));
    }

    /**
     * Get File Size
     */
    public function size(): int
    {
        return $this->size;
    }

    /**
     * Get File Size Formatted (Human Readable)
     * 
     * @param int $precision Decimal places
     * @return string
     * Examples: "1.5 KB", "2.3 MB", "1.2 GB"
     */
    public function sizeFormatted(int $precision = 2)
    {
        $size = $this->size;
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
    public function sizeIn(string $unit = 'KB')
    {
        $size = (float) $this->size;
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
    public function sizeBetween($maxSize, $minSize = null)
    {
        $size = $this->size;

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
     * @return array{width: mixed, height: mixed}
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
     * @return array{path: string, name: string, status: bool}
     */
    public function move(string $destination, ?string $newName = null)
    {
        $fileName = File::name($newName ?: $this->generatedName ?: $this->name());

        // get the path
        $path = Tame::stringReplacer($destination);

        // get actual storage path
        $storagePath = Str::replace($this->name(), '', $path);

        $fullPath = "{$storagePath}/{$fileName}.{$this->extension()}";

        if(!File::isDirectory($storagePath)){
            File::makeDirectory($storagePath);
        }

        $move = @move_uploaded_file($this->tmp(), $fullPath);

        return [
            'path'   => $fullPath,
            'name'   => $fileName,
            'status' => $move,
        ];
    }

    /**
     * Get Upload Error Message
     */
    public function getErrorMessage(): string|null
    {
        return $this->errorMessage;
    }

    /**
     * Delete temporary file
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
     * Normalize error message
     */
    private function errorMessage(): string|null
    {
        $error = $this->error();
        
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
     * Get Temporary Path or null
     */
    private function getTmp(): string|null
    {
        return $this->tmp() ?: 'null';
    }

}