<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Tamedevelopers\Support\Capsule\File;
use Tamedevelopers\Support\Traits\TameTrait;

class Zip {

    use TameTrait;

    /**
     * @var string|null The path to the current archive file
     */
    private $archivePath;

    /**
     * @var string|null The source path for compression
     */
    private $sourcePath;

    /**
     * @var bool Whether the source has been compressed into additional formats
     */
    private $compressed = false;

    /**
     * @var string|null The last created archive path (static)
     */
    private static $lastArchivePath = null;

    /**
     * Constructor
     *
     * @param string|null $sourcePath The source path
     * @param string|null $archivePath The path to the archive file
     */
    public function __construct($sourcePath = null, $archivePath = null)
    {
        $this->sourcePath = $sourcePath;
        $this->archivePath = $archivePath;
    }

    /**
     * Get the path to the current archive file.
     *
     * @return string|null
     */
    public function getArchivePath()
    {
        return $this->archivePath;
    }

    /**
     * Get the last created archive path (static).
     *
     * @return string|null
     */
    public static function getLastArchivePath()
    {
        return self::$lastArchivePath;
    }

    /**
     * Check if the source has been compressed into additional formats.
     *
     * @return bool
     */
    public function isCompressed()
    {
        return $this->compressed;
    }

    /**
     * Zip a file or folder.
     *
     * @param string $sourcePath The path to the file or folder to zip.
     * - [base path will be automatically added]
     *
     * @param string $destinationZip The path for the resulting zip file.
     * - [base path will be automatically added]
     *
     * @param int $level Compression level (0-9, default 0 for no compression).
     * @return Zip Returns a new Zip instance.
     */
    public static function zip($sourcePath, $destinationZip, $level = 0)
    {
        $sourcePath     = self::getBasePath($sourcePath);
        $destinationZip = self::getBasePath($destinationZip);

        // Delete existing file to avoid appending to it
        if (File::exists($destinationZip)) {
            File::delete($destinationZip);
        }

        // If it's a folder, call the zipFolder function
        if (File::isDirectory($sourcePath)) {
            $result = self::zipFolder($sourcePath, $destinationZip, $level);
        } else {
            // If it's a file, create a zip containing just that file
            $zip = new ZipArchive();

            if ($zip->open($destinationZip, ZipArchive::CREATE) !== true) {
                return new self($sourcePath, null);
            }
            
            if(!File::exists($sourcePath)){
                return new self($sourcePath, null);
            }

            // Add the file to the zip
            $zip->addFile($sourcePath, basename($sourcePath));
            $zip->setCompressionName(basename($sourcePath), ZipArchive::CM_DEFLATE, $level);

            $zip->close();

            $result = File::exists($destinationZip);
        }

        self::$lastArchivePath = $result ? $destinationZip : null;
        return new self($sourcePath, $result ? $destinationZip : null);
    }

    /**
     * Gzip a file.
     *
     * @param string $sourcePath The path to the file to gzip.
     * @param string $destinationGz The path for the resulting gz file.
     * @param int $level Compression level (0-9, default 9).
     * @return Zip Returns a new Zip instance.
     */
    public static function gzip($sourcePath, $destinationGz, $level = 9)
    {
        $sourcePath     = self::getBasePath($sourcePath);
        $destinationGz  = self::getBasePath($destinationGz);

        // Delete existing file to avoid appending to it
        if (File::exists($destinationGz)) {
            File::delete($destinationGz);
        }

        if (File::isDirectory($sourcePath)) {
            return new self($sourcePath, null); // Gzip not supported for directories
        }

        if (!File::exists($sourcePath)) {
            return new self($sourcePath, null);
        }

        $gz = gzopen($destinationGz, 'w' . $level);
        if (!$gz) {
            return new self($sourcePath, null);
        }

        $content = File::get($sourcePath);
        gzwrite($gz, $content);
        gzclose($gz);

        $result = File::exists($destinationGz);

        self::$lastArchivePath = $result ? $destinationGz : null;
        return new self($sourcePath, $result ? $destinationGz : null);
    }

    /**
     * Compress the source into gzip format using the archive path as base.
     *
     * @param int $level Compression level for gzip (0-9, default 9).
     * @return Zip Returns $this.
     */
    public function compress($level = 9)
    {
        if (!$this->sourcePath || !$this->archivePath) {
            return $this;
        }

        $base = pathinfo($this->archivePath, PATHINFO_DIRNAME) . '/' . pathinfo($this->archivePath, PATHINFO_FILENAME);
        $gzPath = $base . '.gz';

        // Delete existing files to avoid appending
        if (File::exists($gzPath)) {
            File::delete($gzPath);
        }

        $this->compressed = false;
        if (!File::isDirectory($this->sourcePath)) {
            $gzipResult = self::gzip($this->sourcePath, $gzPath, $level);
            if ($gzipResult->getArchivePath() !== null) {
                $this->compressed = true;
            }
        }

        // For ZIP archives, re-compress with level 9
        if ($this->archivePath && pathinfo($this->archivePath, PATHINFO_EXTENSION) === 'zip') {
            if (File::exists($this->archivePath)) {
                File::delete($this->archivePath);
            }
            $reZipResult = self::zip($this->sourcePath, $this->archivePath, 9);
            if ($reZipResult->getArchivePath() !== null) {
                $this->compressed = true;
            }
        }

        return $this;
    }

    /**
     * Unzip a file or folder.
     *
     * @param  string $sourcePath
     * - [base path will be automatically added]
     * 
     * @param  string $destination
     * - [base path will be automatically added]
     * 
     * @return bool
     */
    public static function unzip($sourcePath, $destination)
    {
        $sourcePath  = self::getBasePath($sourcePath);
        $destination = self::getBasePath($destination);

        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);
        if ($extension === 'zip') {
            return self::unzipFile($sourcePath, $destination);
        } elseif ($extension === 'gz') {
            return self::unzipGz($sourcePath, $destination);
        } elseif (File::isDirectory($sourcePath)) {
            return self::unzipFolder($sourcePath, $destination);
        }

        return false; // Unsupported file type
    }

    /**
     * Download Archive File (zip, gz)
     *
     * @param  bool $unlink
     * @return void
     */
    public function download($unlink = true)
    {
        if (!$this->archivePath) {
            return;
        }

        $filePath = self::getBasePath($this->archivePath);

        if(File::exists($filePath)){
            // Determine content type based on file extension
            $extension = strtolower(pathinfo($this->archivePath, PATHINFO_EXTENSION));
            $contentType = match($extension) {
                'zip' => 'application/zip',
                'gz' => 'application/gzip',
                default => 'application/octet-stream'
            };

            // Set headers to download the archive file
            header("Content-Type: {$contentType}");
            header("Content-Disposition: attachment; filename=" . basename($this->archivePath));
            header('Content-Length: ' . filesize($filePath));

            // Read the file to output the download
            readfile($filePath);

            // Delete the archive file after download (optional)
            if($unlink){
                File::delete($filePath);
            }
        }
    }

    /**
     * Unzip a zip file.
     *
     * @param string $file The path to the zip file.
     * @param string $destination The path to the destination directory where the contents will be extracted.
     * @return bool True if the unzip operation was successful, false otherwise.
     */
    private static function unzipFile($file, $destination)
    {
        // Create object
        $zip = new ZipArchive();

        // Open archive
        if ($zip->open($file) !== true) {
            return false;
        }

        // Extract contents to destination directory
        $zip->extractTo($destination);

        // Close archive
        $zip->close();

        return true;
    }

    /**
     * Zip a folder and its contents.
     *
     * @param string $sourceFolder The path to the folder to zip.
     * @param string $destinationZip The path for the resulting zip file.
     * @param int $level Compression level.
     * @return bool True if the zip operation was successful, false otherwise.
     */
    private static function zipFolder($sourceFolder, $destinationZip, $level = 0)
    {
        $zip = new ZipArchive();

        if ($zip->open($destinationZip, ZipArchive::CREATE) !== true) {
            return false;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceFolder),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();

                // Remove the source folder name from the local path
                $localPath = substr($filePath, strlen($sourceFolder) + 1);

                // Add the contents of the source folder to the zip without the source folder name
                $zip->addFile($filePath, $localPath);
                $zip->setCompressionName($localPath, ZipArchive::CM_DEFLATE, $level);
            }
        }

        $zip->close();

        return File::exists($destinationZip);
    }

    /**
     * Unzip a folder and its contents.
     *
     * @param string $sourceFolder The path to the folder to unzip.
     * @param string $destination The path to the destination directory where the contents will be extracted.
     * @return bool True if the unzip operation was successful, false otherwise.
     */
    private static function unzipFolder($sourceFolder, $destination)
    {
        // Create the destination directory if it doesn't exist
        File::makeDirectory($destination, 0777, true);

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceFolder),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();

                // Remove the source folder name from the local path
                $localPath = substr($filePath, strlen($sourceFolder) + 1);

                // full path
                $destinationPath = $destination . '/' . $localPath;

                // Ensure the destination directory for the file exists
                $destinationDir = dirname($destinationPath);

                // Create the destination directory if it doesn't exist
                File::makeDirectory($destinationDir, 0777, true);

                // copy the contents of the filepath to destinationPath
                File::copy($filePath, $destinationPath);
            }
        }

        return true;
    }

    /**
     * Unzip a gz file.
     *
     * @param string $file The path to the gz file.
     * @param string $destination The path to the destination directory or file.
     * @return bool True if the unzip operation was successful, false otherwise.
     */
    private static function unzipGz($file, $destination)
    {
        $gz = gzopen($file, 'r');
        if (!$gz) {
            return false;
        }

        $content = '';
        while (!gzeof($gz)) {
            $content .= gzread($gz, 8192);
        }
        gzclose($gz);

        $filename = pathinfo($file, PATHINFO_FILENAME);
        $destFile = File::isDirectory($destination) ? $destination . '/' . $filename : $destination;
        File::makeDirectory(dirname($destFile), 0777, true);
        file_put_contents($destFile, $content);

        return true;
    }

}
