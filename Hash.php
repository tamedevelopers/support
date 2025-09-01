<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use Tamedevelopers\Support\Env;
use Tamedevelopers\Support\Capsule\Manager;
use Tamedevelopers\Support\Capsule\CustomException;


final class Hash {
    
    /**
     * Password Encrypter.
     * This function encrypts a password using bcrypt with a generated salt.
     *
     * @param string $password 
     * - The password to encrypt.
     * 
     * @return string 
     * - The encrypted password.
     */
    public static function make($password)
    {
        // Check if the password exceeds the maximum length
        self::passwordLengthVerifier($password, 72);

        // Hash the password using bcrypt with the generated salt
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
    }

    /**
     * Password Verifier.
     * This function verifies a new password against the old hashed password.
     *
     * @param string $newPassword 
     * - The new password to verify.
     * 
     * @param string $oldHashedPassword 
     * - The old hashed password to verify against.
     * 
     * @return bool 
     * - Returns true if the verification is successful, false otherwise.
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
            // Handle the exception silently (turn off error reporting)
            error_reporting(0);

            Manager::setHeaders(404, function() use($e){

                // create error logger
                Env::bootLogger();

                // Trigger a custom error
                trigger_error($e->getMessage(), E_USER_ERROR);
            });
        }
    }

}