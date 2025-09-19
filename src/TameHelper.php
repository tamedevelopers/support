<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Tame;

class TameHelper
{
    /**
     * Batch Deep Ping Email - Verify Multiple Mailbox Existences
     * Processes multiple emails for deep verification. Note: Still processes serially,
     * so for large batches, consider running in background or using emailPing() for speed.
     *
     * @param array $emails Array of email addresses to verify
     * @return array Associative array of email [sorted|unsorted]
     */
    public static function batchDeepEmailPing(array $emails)
    {
        $results = [
            'sorted' => [], 
            'unsorted' => []
        ];

        foreach ($emails as $email) {
            $valid = self::deepEmailPing($email);
            if ($valid) {
                $results['sorted'][] = $email;
            } else {
                $results['unsorted'][] = $email;
            }
        }
        return $results;
    }

    /**
     * Deep Ping Email - Verify Email-Domain Existence, Disposable Emails
     * @param string|null $email The email address to verify
     * @return bool
     */
    public static function deepEmailPing($email = null)
    {
        $email = is_array($email) ? Str::flatten($email) : $email;
        $email = is_array($email) ? ($email[0] ?? null) : $email;

        $hostName = Tame::getHostFromUrl((string) $email);
        $emailPingExist = self::emailPing("noreply@$hostName"); //10x faster than urlExist method

        dd(
            $emailPingExist,
            "u@$hostName"
        );

        if($emailPingExist){
            // check for disposable email
            $disposable = Utility::isDisposableEmail($email);
            if($disposable){
                return false;
            }

            // perform email verification using <internet and server validation>
            $validate = Tame::emailValidator($email, true, true);
            if($validate){
                return true;
            }
        }

        return false;
    }

    /**
     * Ping Email Server
     * Checks if the email server is reachable by attempting a connection to the SMTP server
     * without sending an actual email. This provides a fast way to verify if the domain's mail server is responsive.
     *
     * @param string|null $email The email address to ping
     * @param int $timeout Connection timeout in seconds (default: 3)
     * @return bool True if the mail server is reachable, false otherwise
     */
    public static function emailPing($email = null, $timeout = 3)
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

        if (empty($mxRecords)) {
            return false;
        }

        // Attempt connection on common SMTP ports: 25, 587, 465
        $ports = [25, 587, 465];
        foreach ($mxRecords as $mx) {
            foreach ($ports as $port) {
                $fp = @fsockopen($mx, $port, $errno, $errstr, $timeout);
                if ($fp) {
                    // Connection successful, close immediately
                    fclose($fp);
                    return true;
                }
            }
        }

        // If no MX or port worked
        return false;
    }

}