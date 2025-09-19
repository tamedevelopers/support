<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;



class TameHelper
{

    /**
     * Deep Ping Email - Verify Mailbox Existence
     * Performs a deeper check by simulating an SMTP session to verify if the mailbox exists
     * on the server. This attempts to check if the username (local part) of the email is valid.
     *
     * Note: Not all mail servers support or allow this verification due to anti-spam measures.
     * Some servers may accept all recipients to prevent address harvesting.
     *
     * @param string|null $email The email address to verify
     * @param int $timeout Connection timeout in seconds (default: 10)
     * @return bool True if the mailbox appears to exist, false otherwise
     */
    public static function deepEmailPing($email = null, $timeout = 10)
    {
        // First, validate the email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // Extract the domain and local part
        list($local, $domain) = explode('@', $email);

        // Get MX records
        $mxRecords = [];
        if (!getmxrr($domain, $mxRecords)) {
            return false; // No MX records
        }

        // Get the primary MX server
        $primaryMx = $mxRecords[0] ?? null;
        if (!$primaryMx) {
            return false;
        }

        // Attempt connection on SMTP port (25)
        $fp = @fsockopen($primaryMx, 25, $errno, $errstr, $timeout);
        if (!$fp) {
            return false;
        }

        // Read initial server response
        $response = fgets($fp, 1024);
        if (!$response || !preg_match('/^220/', $response)) {
            fclose($fp);
            return false;
        }

        // Send EHLO
        fputs($fp, "EHLO localhost\r\n");
        $response = '';
        while ($line = fgets($fp, 1024)) {
            $response .= $line;
            if (preg_match('/^\d{3} /', $line)) break; // End of response
        }
        if (!preg_match('/^250/', $response)) {
            fclose($fp);
            return false;
        }

        // Send MAIL FROM (use a dummy sender)
        fputs($fp, "MAIL FROM:<test@example.com>\r\n");
        $response = fgets($fp, 1024);
        if (!preg_match('/^250/', $response)) {
            fclose($fp);
            return false;
        }

        // Send RCPT TO (check the recipient)
        fputs($fp, "RCPT TO:<{$email}>\r\n");
        $response = fgets($fp, 1024);
        $isValid = preg_match('/^250/', $response);

        // Send QUIT
        fputs($fp, "QUIT\r\n");
        fclose($fp);

        return $isValid;
    }
    
    /**
     * Ping Email Server
     * Checks if the email server is reachable by attempting a connection to the SMTP server
     * without sending an actual email. This provides a fast way to verify if the domain's mail server is responsive.
     *
     * @param string|null $email The email address to ping
     * @param int $timeout Connection timeout in seconds (default: 5)
     * @return bool True if the mail server is reachable, false otherwise
     */
    public static function emailPing($email = null, $timeout = 5)
    {
        // First, validate the email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // Extract the domain
        $domain = explode('@', $email)[1];

        // Get MX records
        $mxRecords = [];
        if (!getmxrr($domain, $mxRecords)) {
            return false; // No MX records
        }

        // Get the primary MX server (lowest priority)
        $primaryMx = $mxRecords[0] ?? null;
        if (!$primaryMx) {
            return false;
        }

        // Attempt connection on common SMTP ports: 25, 587, 465
        $ports = [25, 587, 465];
        foreach ($ports as $port) {
            $fp = @fsockopen($primaryMx, $port, $errno, $errstr, $timeout);
            if ($fp) {
                // Connection successful, close immediately
                fclose($fp);
                return true;
            }
        }

        // If no port worked
        return false;
    }


}