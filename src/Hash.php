<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use Tamedevelopers\Support\Capsule\Manager;
use Tamedevelopers\Support\Capsule\CustomException;


final class Hash {
    
    /**
     * Encrypts a password/string using bcrypt.
     *
     * @param string $password The plain text value to encrypt.
     * @return string The hashed password.
     */
    public static function make($password)
    {
        if(!self::passwordLengthVerifier($password, 72)){
            return '';
        }

        // Hash the password using bcrypt with the generated salt
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
    }

    /**
     * Verifies a plain text password against a hashed password.
     *
     * @param string $plainText
     * @param string $hashedPassword
     * @return bool
     */
    public static function verify($plainText, $hashedPassword)
    {
        if (empty($plainText) || empty($hashedPassword)) {
            return false;
        }

        return password_verify($plainText, $hashedPassword);
    }

    /**
     * Alias for verify().
     *
     * @param string $plainText
     * @param string $hashedPassword
     * @return bool 
     */
    public static function check($plainText, $hashedPassword)
    {
        return self::verify($plainText, $hashedPassword);
    }

    /**
     * Throw error if password more than maximum allowed legnth
     *
     * @param  string $password
     * @param  int $maxBytes
     * @return bool
     */
    private static function passwordLengthVerifier($password, $maxBytes = 72)
    {
        try {
            if (strlen($password) > $maxBytes) {
                throw new CustomException(
                    "Password exceeds the maximum allowed length of {$maxBytes} bytes."
                );
            }

            return true;
        } catch (CustomException $e) {
            Manager::silentError($e);
            return false;
        }
    }

}