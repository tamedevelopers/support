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
     * For bulk processing (e.g., 1000+ emails), this method is slow and may not be ideal.
     * Consider using emailPing() for faster domain-only checks.
     *
     * @param string|null $email The email address to verify
     * @param int $timeout Connection timeout in seconds (default: 1)
     * @return bool True if the mailbox appears to exist, false otherwise
     */
    public static function deepEmailPing($email = null, $timeout = 1)
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

        // Try different connection methods and ports
        $connections = [
            ['scheme' => 'tcp', 'host' => $primaryMx, 'port' => 25],
            ['scheme' => 'ssl', 'host' => $primaryMx, 'port' => 465],
            ['scheme' => 'tls', 'host' => $primaryMx, 'port' => 587],
        ];

        foreach ($connections as $conn) {
            $context = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
            $fp = @stream_socket_client("{$conn['scheme']}://{$conn['host']}:{$conn['port']}", $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
            if (!$fp) {
                continue; // Try next connection
            }

            // Read initial server response
            $response = fgets($fp, 1024);
            if (!$response || !preg_match('/^220/', $response)) {
                fclose($fp);
                continue;
            }

            // Send EHLO
            fputs($fp, "EHLO localhost\r\n");
            $response = '';
            $start = time();
            while ((time() - $start) < $timeout && $line = fgets($fp, 1024)) {
                $response .= $line;
                if (preg_match('/^\d{3} /', $line)) break;
            }
            if (!preg_match('/^250/', $response)) {
                // Try HELO if EHLO fails
                fputs($fp, "HELO localhost\r\n");
                $response = fgets($fp, 1024);
                if (!preg_match('/^250/', $response)) {
                    fclose($fp);
                    continue;
                }
            }

            // Send MAIL FROM (use a dummy sender from the same domain if possible, else example.com)
            $sender = $email;
            if (!filter_var($sender, FILTER_VALIDATE_EMAIL)) {
                $sender = 'noreply@example.com';
            }
            fputs($fp, "MAIL FROM:<{$sender}>\r\n");
            $response = fgets($fp, 1024);
            if (!preg_match('/^250/', $response)) {
                fclose($fp);
                continue;
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

        // If all connections failed
        return false;
    }

    /**
     * Ping Email Server
     * Checks if the email server is reachable by attempting a connection to the SMTP server
     * without sending an actual email. This provides a fast way to verify if the domain's mail server is responsive.
     *
     * @param string|null $email The email address to ping
     * @param int $timeout Connection timeout in seconds (default: 1)
     * @return bool True if the mail server is reachable, false otherwise
     */
    public static function emailPing($email = null, $timeout = 1)
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

    /**
     * Batch Deep Ping Email - Verify Multiple Mailbox Existences
     * Processes multiple emails for deep verification. Note: Still processes serially,
     * so for large batches, consider running in background or using emailPing() for speed.
     *
     * @param array $emails Array of email addresses to verify
     * @param int $timeout Connection timeout in seconds per email (default: 1)
     * @return array Associative array of email => bool (true if appears valid)
     */
    public static function batchDeepEmailPing(array $emails, $timeout = 1)
    {
        $results = [];
        foreach ($emails as $email) {
            $results[$email] = self::deepEmailPing($email, $timeout);
        }
        return $results;
    }

}