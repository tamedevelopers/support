<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Traits;

use Closure;
use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Server;

trait TameTrait{

    /**
     * Check if the application is running under a popular PHP framework.
     * Returns true if any supported framework core class is found.
     *
     * Supported frameworks:
     * - Laravel
     * - CodeIgniter
     * - CakePHP
     * - Symfony
     *
     * @return bool
     */
    public static function isAppFramework()
    {
        // using `get_declared_classes()` function will return all classes in your project
        return self::checkAnyClassExists([
            '\Illuminate\Foundation\Application', // Laravel
            '\CI_Controller', // CodeIgniter
            '\Cake\Controller\Controller', // CakePHP
            '\Symfony\Component\HttpKernel\Kernel', // Symfony
            '\Symfony\Component\Routing\Annotation\Route',
        ]);
    }
    
    /**
     * isClosure
     *
     * @param  Closure|null $closure
     * @return bool
     */
    public static function isClosure($closure = null)
    {
        if($closure instanceof Closure){
            return true;
        }

        return false;
    }

    /**
     * OS start
     * 
     * @return void
     */
    public static function obStart()
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
    public static function obFlush()
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
     * @param  Closure|null $closure
     * @return void
     */
    public static function obCronsflush($closure = null)
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

        if(self::isClosure($closure)){
            $closure();
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
     * @param string|null $domain 
     * - The domain extracted from the email address.
     * 
     * @param int $mxRecords 
     * - Counted numbers of MX records associated with the domain.
     * 
     * @return bool 
     * - Whether the email address is valid (true) or not (false).
     */
    private static function verifyDomain_AndMxRecord($domain = null, ?int $mxCount = 0)
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
    private static function openSSLEncrypt()
    {
        return (object) [
            'key'           => bin2hex(random_bytes(8)),
            'cipher_algo'   => 'aes-256-cbc',
            'passphrase'    => bin2hex(random_bytes(4)),
            'options'       => OPENSSL_CIPHER_RC2_40
        ];
    }

    /**
     * Get file modification time
     *
     * @param string|null $path
     * - [full path to file is required]
     * 
     * @return int|bool 
     */
    private static function getFiletime($path = null) 
    {
        $path = self::stringReplacer($path);

        if(self::exists($path)) {
            return filemtime($path);
        }

        return false;
    }
    
    /**
     * Get Base Path
     *
     * @param  string|null $path
     * @return mixed
     */
    private static function getBasePath($path = null)
    {
        // removing default base directory path if added by default
        $path = Str::replace(Server::formatWithBaseDirectory(), '', $path);

        return Server::formatWithBaseDirectory($path);
    }

    /**
     * Replace and recreate path to [assing base_path by default]
     * - (/) slash
     * 
     * @param string|null $path
     * 
     * @return string
     */
    public static function stringReplacer($path = null)
    {
        $replacer = str_replace(
            ['\\', '/'], 
            '/', 
            Str::trim($path)
        );

        return self::getBasePath($replacer);
    }

}