<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;


class Hash 
{
    /**
     * Count
     * @var string
     */
    private const PBKDF2_SALT = "\x2d\xb7\x68\x1a";


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
     * Password Encrypter.
     * This function encrypts a password using bcrypt with a generated salt.
     *
     * @param string $password The password to encrypt.
     * @param int $crypt The cost parameter for bcrypt.
     * @return string The encrypted password.
     */
    static public function encryptPassword($password, $crypt = 10)
    {
        $salt = self::generatePasswordSalt($crypt);
        return crypt($password, $salt);
    }

    /**
     * Password Verifier.
     * This function verifies a new password against the old hashed password.
     *
     * @param string $newPassword The new password to verify.
     * @param string $oldHashedPassword The old hashed password to verify against.
     * @return bool Returns true if the verification is successful, false otherwise.
     */
    static public function verifyPassword($newPassword, $oldHashedPassword)
    {
        return password_verify($newPassword, $oldHashedPassword);
    }

    /**
     * Password Salter.
     * This function generates a salt for password hashing.
     *
     * @param int $crypt The cost parameter for bcrypt.
     * @return string The generated salt.
     */
    static private function generatePasswordSalt($crypt = 10)
    {
        // Define the characters for the salt
        $charSet = 'abcdefghijklmnopqrstuvwxyz';
        $numSet = '0123456789';
        
        // Generate a salt using bcrypt format and shuffle characters
        $salt = '$2y$' . $crypt . '$' . str_shuffle($charSet . $numSet);
        
        return $salt;
    }


}