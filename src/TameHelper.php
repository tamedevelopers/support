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
     * @param bool $disposable Verify disposable emails? Default: true
     * @return array Associative array of email [verified|unverified|runtime]
     */
    public static function batchDeepEmailPing(array $emails, $disposable = true)
    {
        $correct    = 'verified';
        $incorrect  = 'unverified';
        $start      = microtime(true);

        $results = [
            $correct => [],
            $incorrect => []
        ];

        foreach ($emails as $email) {
            $valid = self::deepEmailPing($email, $disposable);
            if ($valid) {
                $results[$correct][] = $email;
            } else {
                $results[$incorrect][] = $email;
            }
        }

        $results['total_emails'] = count($emails);
        $results['runtime'] = microtime(true) - $start;

        return $results;
    }

    /**
     * Deep Ping Email - Verify Email-Domain Existence, Disposable Emails
     * @param string|null $email The email address to verify
     * @param bool $disposable Verify disposable emails? Default: false
     * @return bool
     */
    public static function deepEmailPing($email = null, $disposable = false)
    {
        $email = is_array($email) ? Str::flatten($email) : $email;
        $email = is_array($email) ? ($email[0] ?? null) : $email;

        $hostName = Tame::getHostFromUrl((string) $email);

        // create sample ping email
        $pingEmail = "noreply@{$hostName}";

        // 10x faster than urlExist methods
        // check is there's a valid mx record
        $emailPingExist = self::emailPing($pingEmail);

        if($emailPingExist){
            // check for disposable email
            if($disposable){
                $disposable = Utility::isDisposableEmail($email);
                if($disposable){
                    return false;
                }
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
        $mxRecords = [];

        // Try MX first
        if (checkdnsrr($hostname, 'MX') && getmxrr($hostname, $mxRecords) && !empty($mxRecords)) {
            $targets = $mxRecords; // use MX records
        } else {
            // Fallback to A/AAAA (ensure dns_get_record always returns array)
            $aRecord    = @dns_get_record($hostname, DNS_A)    ?: [];
            $aaaaRecord = @dns_get_record($hostname, DNS_AAAA) ?: [];

            if (empty($aRecord) && empty($aaaaRecord)) {
                return false; // No valid DNS records at all
            }

            // Collect IPs/domains from A and AAAA
            $targets = [];
            if (!empty($aRecord)) {
                $targets = array_merge($targets, array_column($aRecord, 'ip'));
            }
            if (!empty($aaaaRecord)) {
                $targets = array_merge($targets, array_column($aaaaRecord, 'ipv6'));
            }
        }

        // If fsocket enabled, try SMTP connection on port 25
        if ($fsocket && !empty($targets)) {
            foreach ($targets as $target) {
                $fp = @fsockopen($target, 25, $errno, $errstr, $timeout);
                if ($fp && is_resource($fp)) {
                    fclose($fp);
                    return true; // Found a reachable mail server
                }
            }
            return false;
        }

        // If we got here, we have valid MX or A/AAAA records
        return true;
    }

}