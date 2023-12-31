<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Traits;

use Closure;

trait TameTrait{

    /**
     * isClosure
     *
     * @param  Closure|null $closure
     * @return bool
     */
    static public function isClosure($closure = null)
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
     * @param  Closure|null $closure
     * @return void
     */
    static public function obCronsflush($closure = null)
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
    static private function verifyDomain_AndMxRecord($domain = null, ?int $mxCount = 0)
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
            'cipher_algo'   => 'aes-256-cbc',
            'passphrase'    => bin2hex(random_bytes(4)),
            'options'       => OPENSSL_CIPHER_RC2_40
        ];
    }
    
    /**
     * getBasePath
     *
     * @param  string|null $path
     * @return mixed
     */
    static private function getBasePath($path = null)
    {
        return base_path($path);
    }

}