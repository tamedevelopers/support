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
     * Constructor
     *
     * @param string|null $archivePath The path to the archive file
     */
    public function __construct($archivePath = null)
    {
        $this->archivePath = $archivePath;
    }

    /**
     * Get the path to the current archive file.
     *
     * @return mixed
     */
    public function getArchivePath()
    {
        return $this->archivePath;
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
     * @return Zip|bool Returns a new Zip instance on success, false on failure.
     */
    public static function zip($sourcePath, $destinationZip)
    {
        $sourcePath     = self::getBasePath($sourcePath);
        $destinationZip = self::getBasePath($destinationZip);

        // If it's a folder, call the zipFolder function
        if (File::isDirectory($sourcePath)) {
            $result = self::zipFolder($sourcePath, $destinationZip);
        } else {
            // If it's a file, create a zip containing just that file
            $zip = new ZipArchive();

            if ($zip->open($destinationZip, ZipArchive::CREATE) !== true) {
                return false;
            }

            // Add the file to the zip
            $zip->addFile($sourcePath, basename($sourcePath));

            $zip->close();

            $result = File::exists($destinationZip);
        }

        return $result ? new self($destinationZip) : false;
    }

    /**
     * Gzip a file.
     *
     * @param string $sourcePath The path to the file to gzip.
     * @param string $destinationGz The path for the resulting gz file.
     * @return Zip|bool Returns a new Zip instance on success, false on failure.
     */
    public static function gzip($sourcePath, $destinationGz)
    {
        $sourcePath     = self::getBasePath($sourcePath);
        $destinationGz  = self::getBasePath($destinationGz);

        if (File::isDirectory($sourcePath)) {
            return false; // Gzip not supported for directories
        }

        if (!File::exists($sourcePath)) {
            return false;
        }

        $gz = gzopen($destinationGz, 'w9');
        if (!$gz) {
            return false;
        }

        $content = File::get($sourcePath);
        gzwrite($gz, $content);
        gzclose($gz);

        $result = File::exists($destinationGz);

        return $result ? new self($destinationGz) : false;
    }

    /**
     * Rar a file or folder.
     *
     * @param string $sourcePath The path to the file or folder to rar.
     * @param string $destinationRar The path for the resulting rar file.
     * @return Zip|bool Returns a new Zip instance on success, false on failure.
     */
    public static function rar($sourcePath, $destinationRar)
    {
        if (!extension_loaded('rar')) {
            return false;
        }

        $sourcePath     = self::getBasePath($sourcePath);
        $destinationRar = self::getBasePath($destinationRar);

        $rar = \RarArchive::open($destinationRar, \RarArchive::CREATE);
        if (!$rar) {
            return false;
        }

        if (File::isDirectory($sourcePath)) {
            $result = self::rarFolder($sourcePath, $rar);
        } else {
            if (!File::exists($sourcePath)) {
                $rar->close();
                return false;
            }
            $result = $rar->addFile($sourcePath, basename($sourcePath));
        }

        $rar->close();

        $success = $result && File::exists($destinationRar);

        return $success ? new self($destinationRar) : false;
    }

    /**
     * Compress a file or folder into zip, gzip, and rar formats.
     *
     * @param string $sourcePath The path to the file or folder to compress.
     * @param string $destinationBase The base path for the resulting files (without extension).
     * @return Zip|bool Returns a new Zip instance with the zip file on success, false on failure.
     */
    public static function compress($sourcePath, $destinationBase)
    {
        $success = true;
        $zipPath = $destinationBase . '.zip';
        $gzPath = $destinationBase . '.gz';
        $rarPath = $destinationBase . '.rar';

        $zipResult = self::zip($sourcePath, $zipPath);
        if (!File::isDirectory($sourcePath)) {
            $success &= (self::gzip($sourcePath, $gzPath) !== false);
        }
        $success &= (self::rar($sourcePath, $rarPath) !== false);

        return $success && $zipResult ? $zipResult : false;
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
        } elseif ($extension === 'rar') {
            return self::unzipRar($sourcePath, $destination);
        } elseif (File::isDirectory($sourcePath)) {
            return self::unzipFolder($sourcePath, $destination);
        }

        return false; // Unsupported file type
    }

    /**
     * Download Archive File (zip, gz, rar)
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
                'rar' => 'application/x-rar-compressed',
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
     * Static download method for backward compatibility
     *
     * @param  string $fileName
     * @param  bool $unlink
     * @return void
     */
    public static function downloadStatic($fileName, $unlink = true)
    {
        $instance = new self($fileName);
        $instance->download($unlink);
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
     * @return bool True if the zip operation was successful, false otherwise.
     */
    private static function zipFolder($sourceFolder, $destinationZip)
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
     * Rar a folder and its contents.
     *
     * @param string $sourceFolder The path to the folder to rar.
     * @param \RarArchive $rar The rar archive object.
     * @return bool True if the rar operation was successful, false otherwise.
     */
    private static function rarFolder($sourceFolder, $rar)
    {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceFolder),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $localPath = substr($filePath, strlen($sourceFolder) + 1);
                $rar->addFile($filePath, $localPath);
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

    /**
     * Unzip a rar file.
     *
     * @param string $file The path to the rar file.
     * @param string $destination The path to the destination directory.
     * @return bool True if the unzip operation was successful, false otherwise.
     */
    private static function unzipRar($file, $destination)
    {
        if (!extension_loaded('rar')) {
            return false;
        }

        $rar = \RarArchive::open($file);
        if (!$rar) {
            return false;
        }

        $rar->extractTo($destination);
        $rar->close();

        return true;
    }
}
