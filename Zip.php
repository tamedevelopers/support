<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Tamedevelopers\Support\Tame;
use Tamedevelopers\Support\Traits\TameTrait;

class Zip {

    use TameTrait;


    /**
     * Zip a file or folder.
     *
     * @param string $sourcePath The path to the file or folder to zip.
     * - [base path will be automatically added]
     * 
     * @param string $destinationZip The path for the resulting zip file.
     * - [base path will be automatically added]
     * 
     * @return bool True if the zip operation was successful, false otherwise.
     */
    public static function zip($sourcePath, $destinationZip)
    {
        $sourcePath     = self::getBasePath($sourcePath);
        $destinationZip = self::getBasePath($destinationZip);

        // If it's a folder, call the zipFolder function
        if (is_dir($sourcePath)) {
            return self::zipFolder($sourcePath, $destinationZip);
        }

        // If it's a file, create a zip containing just that file
        $zip = new ZipArchive();

        if ($zip->open($destinationZip, ZipArchive::CREATE) !== true) {
            return false;
        }

        // Add the file to the zip
        $zip->addFile($sourcePath, basename($sourcePath));

        $zip->close();

        return file_exists($destinationZip);
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
        
        // If it's a zip file, call the unzipFile function
        if (pathinfo($sourcePath, PATHINFO_EXTENSION) === 'zip') {
            return self::unzipFile($sourcePath, $destination);
        }
        
        // If it's a folder, call the unzipFolder function
        if (is_dir($sourcePath)) {
            return self::unzipFolder($sourcePath, $destination);
        }
        
        return false; // Unsupported file type
    }

    /**
     * Download Zipped File
     *
     * @param  string $fileName
     * @param  bool $unlink
     * @return void
     */
    public static function download($fileName, $unlink = true)
    {
        $zipfilePath = self::getBasePath($fileName);

        if(Tame::exists($zipfilePath)){
            // Set headers to download the ZIP file
            header('Content-Type: application/zip');
            header("Content-Disposition: attachment; filename={$fileName}");
            header('Content-Length: ' . filesize($zipfilePath));
    
            // Read the file to output the download
            readfile($zipfilePath);
    
            // Delete the ZIP file after download (optional)
            if($unlink){
                unlink($zipfilePath);
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
    static private function unzipFile($file, $destination)
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
    static private function zipFolder($sourceFolder, $destinationZip)
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

        return file_exists($destinationZip);
    }

    /**
     * Unzip a folder and its contents.
     *
     * @param string $sourceFolder The path to the folder to unzip.
     * @param string $destination The path to the destination directory where the contents will be extracted.
     * @return bool True if the unzip operation was successful, false otherwise.
     */
    static private function unzipFolder($sourceFolder, $destination)
    {
        // Ensure the destination directory exists
        if (!is_dir($destination)) {
            mkdir($destination, 0777, true);
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

                // full path
                $destinationPath = $destination . '/' . $localPath;

                // Ensure the destination directory for the file exists
                $destinationDir = dirname($destinationPath);

                if (!is_dir($destinationDir)) {
                    mkdir($destinationDir, 0777, true);
                }

                // copy the contents of the filepath to destinationPath
                copy($filePath, $destinationPath);
            }
        }

        return true;
    }
}
