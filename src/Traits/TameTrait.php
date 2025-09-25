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
            '\Illuminate\\Container\\Container', // Laravel
            '\CI_Controller', // CodeIgniter
            '\Cake\Controller\Controller', // CakePHP
            '\Symfony\Component\HttpKernel\Kernel', // Symfony
            '\Symfony\Component\Routing\Annotation\Route',
        ]);
    }

    /**
     * Check if the application is running under Laravel.
     *
     * @return bool
     */
    public static function isLaravel()
    {
        return self::checkAnyClassExists([
            '\Illuminate\Foundation\Application',
            '\Illuminate\\Container\\Container',
        ]);
    }

    /**
     * Check if the application is running under CodeIgniter.
     *
     * @return bool
     */
    public static function isCodeIgniter()
    {
        return self::checkAnyClassExists('\CI_Controller');
    }

    /**
     * Check if the application is running under CakePhp.
     *
     * @return bool
     */
    public static function isCakePhp()
    {
        return self::checkAnyClassExists('\Cake\Controller\Controller');
    }

    /**
     * Check if the application is running under Symfony.
     *
     * @return bool
     */
    public static function isSymfony()
    {
        return self::checkAnyClassExists([
            '\Symfony\Component\HttpKernel\Kernel',
            '\Symfony\Component\Routing\Annotation\Route'
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
     * @param string|null $path (Relative|Absolute Path)
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
    public static function getBasePath($path = null)
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

    /**
     * Parse URL and reliably extract the host, sanitizing protocol typos.
     *
     * @param string $url
     * @return string
     */
    public static function getHostFromUrl($url)
    {
        // 1. Sanitize the protocol: remove common typos like "httpss"
        $url = preg_replace('/^(https?)s:\/\//i', '\1://', $url);

        // 2. Prepend a scheme if it's still missing, to ensure proper parsing
        if (!preg_match('/^https?:\/\//i', $url)) {
            $url = 'http://' . $url;
        }

        $urlParts = parse_url($url);

        // 3. Handle parsing failures
        if ($urlParts === false) {
            return '';
        }

        // 4. Extract the host
        $host = $urlParts['host'] ?? '';
        if (empty($host) && isset($urlParts['path'])) {
            // As a final fallback, take the first part of the path as the host
            $pathParts = explode('/', $urlParts['path'], 2);
            $host = $pathParts[0] ?? '';
        }

        return $host;
    }

    /**
     * Resolve hostname to IP using DNS query to 8.8.8.8
     *
     * @param string $hostname
     * @return string|false
     */
    private static function resolveDNS($hostname)
    {
        $dnsServer = '8.8.8.8';
        $port = 53;

        // Build DNS query
        $id = rand(0, 65535);
        $flags = 0x0100; // Standard query, recursion desired
        $qdcount = 1;
        $header = pack('n*', $id, $flags, $qdcount, 0, 0, 0);

        // Question
        $labels = explode('.', $hostname);
        $qname = '';
        foreach ($labels as $label) {
            $qname .= chr(strlen($label)) . $label;
        }
        $qname .= chr(0);
        $qtype = 1; // A record
        $qclass = 1; // IN
        $question = $qname . pack('n*', $qtype, $qclass);
        $query = $header . $question;

        // Send via UDP
        $socket = @fsockopen('udp://' . $dnsServer, $port, $errno, $errstr, 2);
        if (!$socket) return false;
        fwrite($socket, $query);
        $response = fread($socket, 512);
        fclose($socket);

        // Parse response
        if (strlen($response) < 12) return false;
        $header = unpack('nid/nflags/nqdcount/nancount', substr($response, 0, 8));
        if ($header['ancount'] == 0) return false;

        $pos = 12;
        // Skip question
        while ($response[$pos] != chr(0)) {
            $len = ord($response[$pos]);
            $pos += $len + 1;
        }
        $pos += 5;

        // Answers
        for ($i = 0; $i < $header['ancount']; $i++) {
            if ((ord($response[$pos]) & 0xC0) == 0xC0) {
                $pos += 2;
            } else {
                while ($response[$pos] != chr(0)) {
                    $len = ord($response[$pos]);
                    $pos += $len + 1;
                }
                $pos += 1;
            }
            $type = unpack('n', substr($response, $pos, 2))[1];
            $pos += 8; // Skip type, class, ttl
            $rdlength = unpack('n', substr($response, $pos, 2))[1];
            $pos += 2;
            if ($type == 1 && $rdlength == 4) {
                return inet_ntop(substr($response, $pos, 4));
            }
            $pos += $rdlength;
        }
        return false;
    }

}