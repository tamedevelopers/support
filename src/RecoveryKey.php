<?php

namespace Tamedevelopers\Support;

use Tamedevelopers\Support\Hash;
use Tamedevelopers\Support\Str;

class RecoveryKey
{
    /**
     * Generates a single secure random recovery key.
     *
     * @param int $length Total characters excluding formatting hyphens
     * @return string
     */
    public static function generateCode(int $length = 8) 
    {
        // Generate secure random bytes
        $bytes = random_bytes((int) ceil($length / 2));
        
        // Convert to a clean, easy-to-read hex string
        $code = strtoupper(substr(bin2hex($bytes), 0, $length));
        
        // Split into chunks of 4 characters with a hyphen (e.g., ABCD-1234)
        return implode('-', str_split($code, 4));
    }

    /**
     * Generates a batch of recovery keys for a user.
     *
     * @param int $count Number of backup codes needed
     * @return array
     */
    public static function generateBatchCode(int $count = 8) 
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = self::generateCode();
        }
        return $codes;
    }

    /**
     * Hashes a single recovery code or array of recovery codes.
     *
     * @param string|array $codes Single code or array of unhashed codes
     * @return string|array Single hashed code or array of hashed codes
     */
    public static function hash($codes) 
    {
        if (is_array($codes)) {
            return array_map(function($code) {
                return Hash::make(Str::trim((string) $code));
            }, $codes);
        }

        return Hash::make(Str::trim((string) $codes));
    }

    /**
     * Verifies an input code (or space-separated codes) against stored hashed codes
     * without removing matched codes.
     *
     * @param string|array $inputCode Plain text input from user
     * @param array $hashedCodes Array of stored hashed recovery keys
     * @return bool
     */
    public static function verify($inputCode, array $hashedCodes)
    {
        $inputs = self::normalizeInput($inputCode);

        if (empty($inputs) || empty($hashedCodes)) {
            return false;
        }

        foreach ($inputs as $singleInput) {
            foreach ($hashedCodes as $hashedCode) {
                if (!empty($hashedCode) && Hash::check($singleInput, (string) $hashedCode)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Verifies an input code (or space-separated codes) against stored hashed codes.
     * If matched, removes all matched codes from the array.
     *
     * @param string|array $inputCode Plain text input from user
     * @param array $hashedCodes Array of stored hashed recovery keys
     * @return array Contains 'valid' (bool) and 'remaining' (array of updated hashes)
     */
    public static function verifyAndConsume($inputCode, array $hashedCodes)
    {
        $inputs = self::normalizeInput($inputCode);

        if (empty($inputs) || empty($hashedCodes)) {
            return [
                'valid'     => false,
                'remaining' => $hashedCodes,
            ];
        }

        $isValid = false;

        foreach ($inputs as $singleInput) {
            foreach ($hashedCodes as $index => $hashedCode) {
                if (!empty($hashedCode) && Hash::check($singleInput, (string) $hashedCode)) {
                    unset($hashedCodes[$index]);
                    $isValid = true;
                    break; // Break inner loop once this token matches a hash
                }
            }
        }

        return [
            'valid'     => $isValid,
            'remaining' => array_values($hashedCodes), // Re-index array
        ];
    }

    /**
     * Normalize parsed input into an array of clean string tokens.
     * Handles space-delimited strings ("CODE1 CODE2") or arrays.
     *
     * @param string|array $inputCode
     * @return array
     */
    private static function normalizeInput($inputCode)
    {
        if (is_array($inputCode)) {
            $inputs = $inputCode;
        } else {
            $trimmed = Str::trim($inputCode);
            $inputs = preg_split('/\s+/', $trimmed, -1, PREG_SPLIT_NO_EMPTY);
        }

        return array_map(function($item) {
            return Str::trim($item);
        }, $inputs);
    }

}