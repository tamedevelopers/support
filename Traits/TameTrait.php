<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Traits;

use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;


trait TameTrait{

    /**
     * OS start
     * 
     * @return void
     */
    static public function obStart()
    {
        @ignore_user_abort(true);
        @set_time_limit(0);
        @ob_start();
    }

    /**
     * OBFlush function
     * 
     * @return void
     */
    static public function obFlush()
    {
        // Turn on fastcgi (if available)
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        // Check if headers are already sent
        if (!headers_sent()) {
            // Enable implicit flushing
            ob_implicit_flush(true);

            // Ignore user abort
            ignore_user_abort(true);

            // Disable script timeout
            set_time_limit(0);

            // Set headers for closing the connection and content length
            header("Connection: close");
            header("Content-length: " . ob_get_length());
        }

        // Flush output buffers if active
        while (ob_get_level() > 0) {
            @flush();
            @ob_flush();
            @ob_end_flush();
            @session_write_close();
        }

        // Disable implicit flushing
        ob_implicit_flush(false);
    }
    
    /**
     * OB Crons Flush function
     *
     * @param  callable|null $function
     * @return void
     */
    static public function obCronsflush(callable $function = null)
    {
        // Prevent the script from timing out due to execution time limits
        set_time_limit(0);

        // Close the session to avoid issues with concurrent requests
        session_write_close();

        // Continue script execution even if the client disconnects
        ignore_user_abort(true);

        // Clean (erase) the output buffer and turn off output buffering
        if (ob_get_level() > 0) {
            @ob_end_clean();
        }

        // Start output buffering again
        ob_start();

        // Call the provided function if it's callable
        if (is_callable($function)) {
            $function();
        }

        // Get the content of the output buffer
        $output = ob_get_contents();

        // Close and flush the output buffer
        @ob_end_clean();

        // Send headers to tell the browser to close the connection
        if (!headers_sent()) {
            @header("Connection: close");
            @header("Content-Encoding: none");
            @header("Content-Length: " . strlen($output));
        }

        // Set the HTTP response code
        http_response_code(200);

        // Flush the output buffer to the client
        echo $output;

        // Flush system output buffer
        @flush();
    }

    /**
     * Verify the existence of an email address even when the socket connection is blocked.
     *
     * @param string $domain 
     * - The domain extracted from the email address.
     * 
     * @param int $mxRecords 
     * - Counted numbers of MX records associated with the domain.
     * 
     * @return bool 
     * - Whether the email address is valid (true) or not (false).
     */
    static private function verifyDomain_AndMxRecord(?string $domain = null, ?int $mxCount = 0)
    {
        // Method 2: Use DNS check on domain A record
        $domainRecords = dns_get_record($domain, DNS_A);
        if (count($domainRecords) > 0 && $mxCount > 0) {
            return true; // Consider it valid based on having domain A record
        }
        
        return false;
    }

    /**
     * Create OPEN SSL Encryption
     * 
     * @return object
     */
    static private function openSSLEncrypt()
    {
        return (object) [
            'key'           => bin2hex(random_bytes(8)),
            'cipher_algo'   => 'BF-CBC',
            'passphrase'    => bin2hex(random_bytes(4)),
            'options'       => OPENSSL_CIPHER_RC2_40
        ];
    }
    
    /**
     * getBasePath
     *
     * @param  mixed $path
     * @return mixed
     */
    static private function getBasePath(?string $path = null)
    {
        return self::stringReplacer(base_path()) . $path;
    }
    
    /**
     * getPublicPath
     *
     * @param  mixed $path
     * @return mixed
     */
    static private function getPublicPath(?string $path = null)
    {
        return self::stringReplacer(base_path()) . $path;
    }
    
    /**
     * getStoragePath
     *
     * @param  mixed $path
     * @return mixed
     */
    static private function getStoragePath(?string $path = null)
    {
        return self::stringReplacer(base_path()) . $path;
    }

    /**
     * getAppPath
     *
     * @param  mixed $path
     * @return mixed
     */
    static private function getAppPath(?string $path = null)
    {
        return self::stringReplacer(base_path()) . $path;
    }
    
    /**
     * getSvgPath
     *
     * @param  mixed $path
     * @return mixed
     */
    static private function getSvgPath(?string $path = null)
    {
        return self::stringReplacer(base_path()) . $path;
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
        $zip->extractTo(self::getBasePath($destination));

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
                $localPath = substr($filePath, strlen($sourceFolder) + 1);
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
                $localPath = substr($filePath, strlen($sourceFolder) + 1);
                $destinationPath = $destination . '/' . $localPath;
                // Ensure the destination directory for the file exists
                $destinationDir = dirname($destinationPath);
                if (!is_dir($destinationDir)) {
                    mkdir($destinationDir, 0777, true);
                }
                copy($filePath, $destinationPath);
            }
        }

        return true;
    }

}