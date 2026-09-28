<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use Tamedevelopers\Support\QRCode;
use Tamedevelopers\Support\Str;


/**
 * Service for generating and verifying Time-based One-Time Passwords (TOTP)
 * Compliant with RFC 6238 and RFC 4226.
 */
class TOTP
{
    /**
     * @var int Number of digits in the generated OTP code (typically 6 or 8).
     */
    private int $digits;

    /**
     * @var int Time step period in seconds (default is 30 seconds).
     */
    private int $period;

    /**
     * @var string Hash algorithm used for HMAC calculation ('sha1', 'sha256', or 'sha512').
     */
    private string $algorithm;

    /**
     * TOTPService constructor.
     *
     * @param int    $digits    Length of the OTP output code (default: 6).
     * @param int    $period    Time interval window in seconds (default: 30).
     * @param 'sha1'|'sha256'|'sha512' $algorithm HMAC hashing algorithm (default: 'sha1').
     */
    public function __construct(int $digits = 6, int $period = 30, string $algorithm = 'sha1')
    {
        $this->digits($digits);
        $this->period($period);
        $this->algorithm($algorithm);
    }

    /**
     * Set the validity or expiration period for the QR code.
     *
     * @param int $period Time duration (e.g., in seconds or days depending on configuration).
     * @return $this
     */
    public function period(int $period)
    {
        // Default to 30s if period is invalid
        $this->period = $period > 0 ? $period : 30;
        return $this;
    }

    /**
     * Set the number of digits for the OTP code.
     *
     * @param int $digits Output digit length (typically 6 or 8).
     * @return $this
     */
    public function digits(int $digits)
    {
        // Enforce safe digit lengths (typically 6 or 8)
        $this->digits = in_array($digits, [6, 8], true) ? $digits : 6;
        return $this;
    }

    /**
     * Set the HMAC algorithm.
     *
     * @param 'sha1'|'sha256'|'sha512' $algorithm Algorithm name
     * @return $this
     */
    public function algorithm(string $algorithm)
    {
        $algo = Str::lower($algorithm);

        // Only accept RFC-supported algorithms
        $this->algorithm = in_array($algo, ['sha1', 'sha256', 'sha512'], true) ? $algo : 'sha1';
        return $this;
    }

    /**
     * Generates a random Base32 encoded secret key suitable for TOTP configurations.
     *
     * @param int $length Number of random bytes to generate before Base32 encoding (default: 16).
     * @return string High-entropy Base32 encoded secret string.
     * 
     * @throws \Exception If a secure source of randomness cannot be found.
     */
    public function generateSecret(int $length = 16): string
    {
        $alphabet   = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bytes      = random_bytes($length);
        $secret     = '';

        for ($i = 0; $i < $length; $i++) {
            $secret .= $alphabet[ord($bytes[$i]) % 32];
        }

        return $secret;
    }

    /**
     * Generates a numeric TOTP code for a given secret key and timestamp.
     *
     * @param string   $secret    Base32 encoded user secret key.
     * @param int|null $timestamp Unix timestamp to calculate the code for (defaults to current time).
     * @return string Zero-padded OTP code matching configured digit count.
     */
    public function generateCode(string $secret, ?int $timestamp = null): string
    {
        $timestamp = $timestamp ?? time();
        $timeStep = (int) floor($timestamp / $this->period);

        $binarySecret = $this->base32Decode($secret);
        
        // Pack timeStep as 64-bit Big-Endian (RFC 6238 requirement)
        $timeBuffer = pack('N*', 0, $timeStep);

        // HMAC calculation
        $hash = hash_hmac($this->algorithm, $timeBuffer, $binarySecret, true);

        // Dynamic truncation (RFC 4226)
        $offset         = ord($hash[strlen($hash) - 1]) & 0x0F;
        $unpacked       = unpack('N', substr($hash, $offset, 4));
        $truncatedHash  = $unpacked[1] & 0x7FFFFFFF;

        $code = $truncatedHash % (10 ** $this->digits);

        return str_pad((string) $code, $this->digits, '0', STR_PAD_LEFT);
    }

    /**
     * Render 1Pass (2FA/TOTP) credentials and save the generated QR code.
     *
     * @param string      $accountName The user or account identifier (e.g., email or username).
     * @param string      $issuerName  The application or organization name issuing the 2FA token.
     * @param string|null $path        Target file path to save the QR code image, or null.
     * @param array{
     *  pattern: 'classic'|'rounded'|'thin'|'smooth'|'circle'|'leaf'|'inverted'|'pillow'|'finder'|'scallop',
     *  iconPath?: string,
     *  shape: 'classic'|'rounded'|'thin'|'smooth'|'circle'|'leaf'|'inverted'|'pillow'|'finder'|'scallop',
     *  innerShape: 'classic'|'rounded'|'thin'|'smooth'|'circle'|'leaf'|'inverted'|'pillow'|'finder'|'scallop',
     *  outerShape: 'classic'|'rounded'|'thin'|'smooth'|'circle'|'leaf'|'inverted'|'pillow'|'finder'|'scallop',
     *  showIcon?: bool,
     * } $qrOptions
     * 
     * @return array{
     *  secret: string, 
     *  encrypt: string, 
     *  otpUri: string, 
     *  name: string|null,
     *  path: string|null,
     *  data: string|null,
     *  image: string|null,
     * } Array containing secret, OTP URI, and image representation.
     */
    public function render($accountName, $issuerName, $path = null, $qrOptions = []): array
    {
        $userSecret = $this->generateSecret();

        $otpUri = $this->getProvisioningUri(
            secret: $userSecret,
            accountName: $accountName,
            issuer: $issuerName
        );

        $qr = new QRCode($path);

        if(!empty($qrOptions)){
            if(isset($qrOptions['pattern'])){
                $qr->pattern($qrOptions['pattern']);
            }
            if(isset($qrOptions['iconPath'])){
                $qr->iconPath($qrOptions['iconPath']);
            }
            if(isset($qrOptions['showIcon'])){
                $qr->showIcon($qrOptions['showIcon']);
            }
            if(isset($qrOptions['shape'])){
                $qr->shape($qrOptions['shape']);
            }
            if(isset($qrOptions['innerShape'])){
                $qr->innerShape($qrOptions['innerShape']);
            }
            if(isset($qrOptions['outerShape'])){
                $qr->outerShape($qrOptions['outerShape']);
            }
        }
        
        $qr->addText($otpUri)->save();

        return [
            'secret'    => $userSecret,
            'encrypt'   => Str::encrypt($userSecret),
            'otpUri'    => $otpUri,
            'name'      => $qr->getPath('name'),
            'path'      => $qr->getPath('path'),
            'data'      => $qr->getData(),
            'image'     => $qr->toImage(),
        ];
    }

    /**
     * Verifies a user-provided OTP code against a secret key with optional time-drift tolerance.
     *
     * @param string $secret        Base32 encoded user secret key.
     * @param string $userInputCode The code provided by the user to verify.
     * @param int $window Clock drift window (default: 0 for strict current time).
     * @param int|null $usedTimeStep Optional parameter to prevent code replay attacks.
     * @return bool True if valid within window, false otherwise.
     */
    public function verify(string $secret, string $userInputCode, int $window = 0, ?int &$usedTimeStep = null): bool
    {
        $userInputCode = preg_replace('/\s+/', '', $userInputCode);

        if (strlen($userInputCode) !== $this->digits || !is_numeric($userInputCode)) {
            return false;
        }

        $currentTime = time();
        $currentStep = (int) floor($currentTime / $this->period);

        for ($i = -$window; $i <= $window; $i++) {
            $targetStep = $currentStep + $i;

            // Skip if this time step was already used by the user
            if ($usedTimeStep !== null && $targetStep <= $usedTimeStep) {
                continue;
            }

            $targetTimestamp = $targetStep * $this->period;
            $expectedCode = $this->generateCode($secret, $targetTimestamp);

            if (hash_equals($expectedCode, $userInputCode)) {
                // Record the time step so it cannot be reused
                $usedTimeStep = $targetStep;
                return true;
            }
        }

        return false;
    }

    /**
     * Generates a standard `otpauth://` URI for QR codes compatibility with 
     * Google Authenticator, Authy, and standard password managers.
     *
     * @param string $secret      Base32 encoded user secret key.
     * @param string $accountName User identifier, typically email or username (e.g., 'user@example.com').
     * @param string $issuer      Application or organization name (e.g., 'DFBank').
     * @return string Fully qualified otpauth URI string.
     */
    public function getProvisioningUri(string $secret, string $accountName, string $issuer): string
    {
        $label = rawurlencode($issuer) . ':' . rawurlencode($accountName);
        $params = [
            'secret'    => $secret,
            'issuer'    => $issuer,
            'period'    => $this->period,
            'digits'    => $this->digits,
            'algorithm' => strtoupper($this->algorithm),
        ];

        return 'otpauth://totp/' . $label . '?' . http_build_query($params);
    }

    /**
     * Decodes a Base32 string into raw binary data.
     *
     * @param string $base32 The Base32 encoded string to decode.
     * @return string Binary representation of the Base32 string.
     */
    private function base32Decode(string $base32): string
    {
        $base32 = strtoupper(preg_replace('/[^A-Z2-7]/', '', $base32));
        if (empty($base32)) {
            return '';
        }

        $alphabet   = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $buffer     = 0;
        $bitsLeft   = 0;
        $binary     = '';

        for ($i = 0; $i < strlen($base32); $i++) {
            $val = strpos($alphabet, $base32[$i]);
            if ($val === false) {
                continue;
            }

            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $binary .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $binary;
    }

}