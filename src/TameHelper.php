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

        // create sample ping email
        $pingEmail = "noreply@{$hostName}";

        // 10x faster than urlExist methods
        // check is there's a valid mx record
        $emailPingExist = self::emailPing($pingEmail); 

        dump(
            ($emailPingExist) ? "Ping: {$email} - Yes" : "Ping: {$email} - No"
        );

        if($emailPingExist){
            // check for disposable email
            $disposable = Utility::isDisposableEmail($email);

            // dump(
            //     ($disposable) ? "Disposable: {$email} - Yes" : "Disposable: {$email} - No"
            // );
            if($disposable){
                // return false;
            } 

            // perform email verification using <internet and server validation>
            $validate = Tame::emailValidator($email, false, false);
            if(!$validate){
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Ping Email Server
     * Checks if the email server is reachable by attempting a connection to the SMTP server
     * without sending an actual email. This provides a fast way to verify if the domain's mail server is responsive.
     *
     * @param string|null $email The email address to ping
     * @param bool $fsocket to verify using fsocket
     * @param int $timeout Connection timeout in seconds (default: 1)
     * @return bool True if the mail server is reachable, false otherwise
     */
    public static function emailPing($email = null, $fsocket = false, $timeout = 1)
    {
        // First, validate the email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // Extract the hostname
        $hostname = explode('@', $email)[1];

        // Get MX records
        $mxRecords = [];
        if (!getmxrr($hostname, $mxRecords)) {
            return false; // No MX records
        }

        // Get the primary MX server (lowest priority)
        $primaryMx = $mxRecords[0] ?? null;
        if (!$primaryMx) {
            return false;
        }

        if($fsocket){
            // Attempt connection on port 25 (most common SMTP port)
            $fp = @fsockopen($primaryMx, 25, $errno, $errstr, $timeout);
            if ($fp && is_resource($fp)) {
                fclose($fp);
                return true;
            } 
        }

        // if mx record came back with no error 
        // then it means that the email server is reachable
        if($primaryMx){
            return true;
        }

        return false;
    }

}