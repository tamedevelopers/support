<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use Tamedevelopers\Support\Capsule\Manager;
use Tamedevelopers\Support\Capsule\CustomException;


final class Hash {
    
    /**
     * This function encrypts a password using bcrypt with a generated salt.
     *
     * @param string $password      The password to encrypt.
     * @return string   The encrypted password.
     */
    public static function make($password)
    {
        // Check if the password exceeds the maximum length
        self::passwordLengthVerifier($password, 72);

        // Hash the password using bcrypt with the generated salt
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
    }

    /**
     * This function verifies a new password against the old hashed password.
     *
     * @param string $newPassword    The new password to verify.
     * @param string $oldHashedPassword     The old hashed password to verify against.
     * @return bool 
     */
    public static function check($newPassword, $oldHashedPassword)
    {
        return password_verify($newPassword, $oldHashedPassword);
    }

    /**
     * Throw error if password more than maximum allowed legnth
     *
     * @param  mixed $password
     * @param  mixed $maxPasswordLength
     * @return void
     */
    private static function passwordLengthVerifier($password, $maxPasswordLength = 72)
    {
        try {
            if (mb_strlen($password, 'UTF-8') > $maxPasswordLength) {
                throw new CustomException(
                    "Password exceeds the maximum allowed length of {$maxPasswordLength} bytes."
                );
            }
        } catch (CustomException $e) {
            Manager::silentError($e);
        }
    }

}