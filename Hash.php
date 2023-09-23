<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use Tamedevelopers\Support\Env;
use Tamedevelopers\Support\Capsule\Manager;
use Tamedevelopers\Support\Capsule\CustomException;


class Hash 
{
    /**
     * Count
     * @var string
     */
    private const PBKDF2_SALT = "\x2d\xb7\x68\x1a";


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
    static public function make($password)
    {
        // Check if the password exceeds the maximum length
        if (mb_strlen($password, 'UTF-8') > 72) {
            self::passwordLengthVerifier($password, 72);
        }

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
    static public function check($newPassword, $oldHashedPassword)
    {
        return password_verify($newPassword, $oldHashedPassword);
    }

    /**
     * Hash String
     *
     * @param  string $string
     * @param  int $length
     * @param  string $type
     * @param  int $interation
     * @return void
     */
    static public function stringHash(?string $string, $length = 100, $type = 'sha256', $interation = 100)
    {
        return hash_pbkdf2($type, mt_rand() . $string, self::PBKDF2_SALT, $interation, $length);
    }

    /**
     * Throw error if password more than maximum allowed legnth
     *
     * @param  mixed $password
     * @param  mixed $maxPasswordLength
     * @return void
     */
    static private function passwordLengthVerifier($password, $maxPasswordLength = 10)
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