<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Traits;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\PHPMailer;
use Tamedevelopers\Support\Capsule\File;
use Tamedevelopers\Support\Mail;
use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Tame;


trait MailTrait{

    /**
     * mailer
     *
     * @var PHPMailer
     */
    private $mailer;

    /**
     * recipients
     *
     * @var array
     */
    private $recipients = [
        'to'  => [],
        'cc'  => false,
        'bcc' => false,
        'reply_to' => false,
    ];

    /**
     * Options
     *
     * @var array
     */
    private $options = [];

    /**
     * smtpData
     *
     * @var array
     */
    private $smtpData = [];

    /**
     * attachments
     *
     * @var array
     */
    private $attachments = [];

    /**
     * Delete Attachment
     *
     * @var array
     */
    private $deleteAttachment = false;

    /**
     * Flush buffering
     *
     * @var array
     */
    private $flushBuffering = false;
    
    /**
     * config
     *
     * @var array
     */
    private $config = [];
    
    /**
     * driver
     *
     * @var string
     */
    private $driver = null;
    
    /**
     * provider
     *
     * @var string
     */
    private $provider = null;
    
    /**
     * debug
     *
     * @var int
     */
    private $debug = 0;
    
    /**
     * timeout
     *
     * @var int
     */
    private $timeout = 10;
    
    /**
     * keepAlive
     *
     * @var int
     */
    private $keepAlive = true;
    
    /**
     * subject
     *
     * @var string
     */
    private $subject;
    
    /**
     * body
     *
     * @var string
     */
    private $body;
    
    /**
     * altbody
     *
     * @var string
     */
    private $altbody = false;
    
    /**
     * constantName
     *
     * @var string
     */
    private static $constantName = 'TAME_MAILER_CONFIG___';
    
    /**
     * static
     *
     * @var mixed
     */
    private static $staticData;
    

    /**
     * Convert input to an array of valid email addresses
     *
     * @param string|array|null $emails A string of comma-separated email addresses or an array of email addresses
     * @param string|null $mode
     *
     * @return mixed
     */
    public function convert(string|array|null $emails, $mode = null)
    {
        if (is_null($emails)) {
            return ["email" => [], "count" => 0];
        }

        // Normalize input to an array
        $emailArray = is_array($emails) 
                ? $emails 
                : explode(',', str_replace(["\r", "\n", " "], "", $emails));

        $emailArray = Str::flattenValue($emailArray);

        // Filter and validate email addresses
        $validEmails = array_filter($emailArray, function ($email) {
            return filter_var(trim($email), FILTER_VALIDATE_EMAIL);
        });

        // Return the array of valid email addresses and their count
        $array = [
            "email" => array_values($validEmails), // Reset array keys
            "count" => count($validEmails)
        ];

        return $array[$mode] ?? $array;
    }

    /**
     * Add Reply to recipients.
     *
     * @param string $emails
     * @return $this
     */
    public function __replyTo(...$emails)
    {
        [$address, $name] = [$emails[0], $emails[1] ?? null];

        $this->recipients['reply_to'] = [$address, $name];

        return $this;
    }

    /**
     * Set the email altbody.
     *
     * @param string $body
     * @return $this
     */
    public function __altBody($body)
    {
        $this->altbody = $body;

        return $this;
    }
    
    /**
     * addCC
     * 
     * @param array $payload
     * @param bool $isAPI
     */
    private function addCC(&$payload = [], $isAPI = false): void
    {
        if(!empty($this->recipients['cc'])){
            foreach($this->recipients['cc'] as $cc){
                if(Tame()->emailValidator($cc, false)){
                    if(!$isAPI){
                        $this->mailer->addCC($cc);
                    } else{
                        switch($this->provider){
                            case 'sendgrid':
                                $payload['personalizations'][0]['cc'][] = ['email' => $cc];
                                break;
                            case 'mailjet':
                                $payload['CcAddresses'][] = ['Email' => $cc];
                                break;
                            case 'brevo':
                                $payload['cc'][] = ['email' => $cc];
                                break;
                            case 'postmark':
                                $payload['Cc'][] = $cc;
                                break;
                            case 'mailgun':
                                $payload['cc'][] = $cc;
                                break;
                            case 'sparkpost':
                                $payload['recipients'][] = ['address' => ['email' => $cc]];
                                break;
                            default:
                                $payload['cc'][] = ['email_address' => ['address' => $cc]];
                                break;
                        }
                    }
                }
            }
        }
    }
    
    /**
     * addBCC
     * 
     * @param array $payload
     * @param bool $isAPI
     */
    private function addBCC(&$payload = [], $isAPI = false): void
    {
        if(!empty($this->recipients['bcc'])){
            foreach($this->recipients['bcc'] as $bcc){
                if(Tame()->emailValidator($bcc, false)){
                    if(!$isAPI){
                        $this->mailer->addBCC($bcc);
                    } else{
                        switch($this->provider){
                            case 'sendgrid':
                                $payload['personalizations'][0]['bcc'][] = ['email' => $bcc];
                                break;
                            case 'mailjet':
                                $payload['BccAddresses'][] = ['Email' => $bcc];
                                break;
                            case 'brevo':
                                $payload['bcc'][] = ['email' => $bcc];
                                break;
                            case 'postmark':
                                $payload['Bcc'][] = $bcc;
                                break;
                            case 'mailgun':
                                $payload['bcc'][] = $bcc;
                                break;
                            case 'sparkpost':
                                $payload['recipients'][] = ['address' => ['email' => $bcc]];
                                break;
                            default:
                                $payload['bcc'][] = ['email_address' => ['address' => $bcc]];
                                break;
                        }
                    }
                }
            }
        }
    }
    
    /**
     * addReplyTo
     * 
     * @param array $payload
     * @param bool $isAPI
     */
    private function addReplyTo(&$payload = [], $isAPI = false): void
    {
        $replyTo    = $this->recipients['reply_to'];
        $address    = $replyTo[0] ?? null;
        $name       = $replyTo[1] ?? '';

        if(!empty($replyTo) && !empty($address)){
            if(!$isAPI){
                $this->mailer->addReplyTo($address, $name);
            } else{
                switch($this->provider){
                    case 'sendgrid':
                        $payload['personalizations'][0]['reply_to'] = ['email' => $address, 'name' => $name];
                        break;
                    case 'mailjet':
                        $payload['ReplyTo'] = ['Email' => $address, 'Name' => $name];
                        break;
                    case 'brevo':
                        $payload['replyTo'] = ['email' => $address, 'name' => $name];
                        break;
                    case 'postmark':
                        $payload['ReplyTo'] = $address;
                        break;
                    case 'mailgun':
                        $payload['h:Reply-To'] = $address;
                        break;
                    case 'sparkpost':
                        $payload['recipients'][] = ['address' => ['email' => $address, 'header' => ['Reply-To' => $address]]];
                        break;
                    default:
                        $payload['reply_to'] = ['address' => $address, 'name' => $name];
                        break;
                }
            }
        }
    }
    
    /**
     * addAltBody
     * 
     * @param array $payload
     * @param bool $isAPI
     */
    private function addAltBody(&$payload = [], $isAPI = false): void
    {
        // If support alternative message
        if(!empty($this->altbody)){
            if(!$isAPI){
                $this->mailer->AltBody = $this->altbody; 
            } else{
                switch($this->provider){
                    case 'sendgrid':
                        // Add a second content type for plain text
                        $payload['content'][] = ['type' => 'text/plain', 'value' => $this->altbody];
                        break;
                    case 'mailgun':
                        $payload['text'] = $this->altbody;
                        break;
                    case 'postmark':
                        $payload['TextBody'] = $this->altbody;
                        break;
                    case 'brevo':
                        $payload['textContent'] = $this->altbody;
                        break;
                    case 'mailjet':
                        $payload['TextPart'] = $this->altbody;
                        break;
                    case 'sparkpost':
                        // SparkPost usually uses "content" array with text
                        if(!isset($payload['content'])) $payload['content'] = [];
                        $payload['content'][] = ['type' => 'text/plain', 'value' => $this->altbody];
                        break;
                    default:
                        // Zeptomail / default
                        $payload['textbody'] = $this->altbody;
                        break;
                }
            }
        }
    }

    /**
     * Delete attachment
     */
    private function deleteAttachment(): void
    {
        // if attachment delete is allowed
        if($this->deleteAttachment){
            foreach($this->attachments as $path => $name){
                File::delete($path);
            }
        }
    }

    /**
     * Build API Base Payload
     * 
     * @param string $email
     * @return array
     */
    private function buildAPIPayload($email)
    {
        $from = [
            'address' => $this->smtpData['from_email'],
            'name'    => $this->smtpData['from_name']
        ];

        return match($this->provider) {
            'sendgrid' => [
                'personalizations' => [[ 'to' => [[ 'email' => $email ]] ]],
                'from' => $from,
                'subject' => $this->subject,
                'content' => [[ 'type' => 'text/html', 'value' => $this->body ]]
            ],
            'mailgun' => [
                'from'    => "{$from['name']} <{$from['address']}>",
                'to'      => $email,
                'subject' => $this->subject,
                'html'    => $this->body
            ],
            'mailjet' => [
                'Messages' => [[
                    'From' => $from,
                    'To'   => [['Email' => $email]],
                    'Subject' => $this->subject,
                    'HTMLPart' => $this->body
                ]]
            ],
            'postmark' => [
                'From'    => $from['address'],
                'To'      => $email,
                'Subject' => $this->subject,
                'HtmlBody'=> $this->body
            ],
            'aws' => [
                'Source' => $from['address'],
                'Destination' => ['ToAddresses' => [$email]],
                'Message' => [
                    'Subject' => ['Data' => $this->subject, 'Charset' => 'UTF-8'],
                    'Body'    => ['Html' => ['Data' => $this->body, 'Charset' => 'UTF-8']]
                ]
            ],
            'sparkpost' => [
                'options' => ['sandbox' => false],
                'content' => [
                    'from'    => $from,
                    'subject' => $this->subject,
                    'html'    => $this->body
                ],
                'recipients' => [['address' => ['email' => $email]]]
            ],
            'brevo' => [
                'sender' => $from,
                'to' => [['email' => $email]],
                'subject' => $this->subject,
                'htmlContent'=> $this->body
            ],
            default => [ // zeptomail
                'from'    => $from,
                'to'      => [['email_address' => ['address' => $email]]],
                'subject' => $this->subject,
                'htmlbody'=> $this->body
            ]
        };
    }

    /**
     * Get Api Default Header
     */
    private function getDefaultHeaders(): array
    {
        $token      = $this->smtpData['api_token'] ?? '';
        $secret     = $this->smtpData['api_secret'] ?? '';
        $headers    = ["accept: application/json"];

        switch ($this->provider) {
            case 'sendgrid':
                $headers[] = "Authorization: Bearer {$token}";
                $headers[] = "Content-Type: application/json";
                break;
            case 'mailgun':
                $headers[] = "Authorization: Basic " . base64_encode("api:{$token}");
                break;
            case 'mailjet':
                if (empty($secret)) {
                    throw new \Exception("Mailjet requires API_SECRET.", 512);
                }

                $headers[] = "Authorization: Basic " . base64_encode("{$token}:{$secret}");
                $headers[] = "Content-Type: application/json";
                break;
            case 'postmark':
                $headers[] = "X-Postmark-Server-Token: {$token}";
                $headers[] = "Content-Type: application/json";
                break;
            case 'sparkpost':
            case 'brevo':
                $headers[] = "api-key: {$token}";
                $headers[] = "Content-Type: application/json";
                break;
            default: // zeptomail
                $headers[] = "authorization: {$token}";
                $headers[] = "cache-control: no-cache";
                $headers[] = "Content-Type: application/json";
        }

        return $headers;
    }

    /**
     * Get Api Header (Amazon SES) Amazon Simple Email Service
     * @return array
     */
    private function getApiHeaders()
    {
        if($this->isAWS()){
            return $this->getDefaultHeaders();
        }

        // Ensure AWS SDK exists
        if (!class_exists('\Aws\SesV2\SesV2Client')) {
            throw new \Exception(
                "AWS SDK not installed. Run: composer require aws/aws-sdk-php",
                520
            );
        }

        // SDK handles signing internally
        return [];
    }

    /**
     * Build Postfields multiparts
     * 
     * @param array $payload
     * @return array
     */
    private function buildPostfieldMultiparts($payload)
    {
        $postFields = [];

        switch($this->provider){
            case 'sendgrid':
                // Ensure 'from' is an object with 'email' and optional 'name'
                $from = $payload['from'] ?? [];
                if (!isset($from['email'])) {
                    $from = [
                        'email' => $this->smtpData['from_email'] ?? '',
                        'name'  => $this->smtpData['from_name'] ?? ''
                    ];
                }

                $postFields = [
                    'personalizations' => $payload['personalizations'] ?? [],
                    'from'             => $from,
                    'subject'          => $payload['subject'] ?? '',
                    'content'          => $payload['content'] ?? []
                ];
                break;

            case 'mailjet':
                $postFields = $payload['Messages'] ?? [];
                break;

            case 'mailgun':
            case 'postmark':
            case 'brevo':
            case 'sparkpost':
                // Use as-is, attachPostfieldMultiparts will handle CC/BCC/ReplyTo
                $postFields = $payload;
                break;

            default: // Zeptomail and others
                $postFields = [
                    'from'     => $payload['from'] ?? [],
                    'to'       => $payload['to'] ?? [],
                    'subject'  => $payload['subject'] ?? '',
                    'htmlbody' => $payload['htmlbody'] ?? '',
                    'textbody' => $payload['textbody'] ?? ''
                ];
                break;
        }

        return $postFields;
    }

    /**
     * Attach Postfields multiparts
     * 
     * @param array $postFields
     * @param array $payload
     */
    private function attachPostfieldMultiparts(&$postFields, $payload): void
    {
        // Add CC, BCC, Reply-To if provider supports them
        if (!empty($payload['cc'])) {
            $postFields['cc'] = $payload['cc'];
        }

        if (!empty($payload['bcc'])) {
            $postFields['bcc'] = $payload['bcc'];
        }

        if (!empty($payload['reply_to'])) {
            $postFields['reply_to'] = $payload['reply_to'];
        }

        // Add attachments, normalized for SendGrid / Mailjet / Brevo / others
        if(!empty($this->attachments)){
            $attachments = [];
            foreach ($this->attachments as $path => $name) {
                if (File::exists($path)) {
                    $attachments[] = match($this->provider){
                        'sendgrid', 'mailjet', 'brevo', 'postmark' => [
                            'content' => Tame::imageToBase64($path, false, true),
                            'filename' => $name,
                            'type' => mime_content_type($path)
                        ],
                        default => [
                            'name' => $name,
                            'mime_type' => mime_content_type($path),
                            'content' => Tame::imageToBase64($path, false, true)
                        ]
                    };
                }
            }

            if(!empty($attachments)){
                $postFields['attachments'] = $attachments;
            }
        }
    }

    /**
     * Standardize attachments for the attach method.
     *
     * @param mixed $attachments A string, single array, or an array of attachments.
     * @return array Associative array with file paths as keys and their names as values.
     */
    private function formatAttachments($attachments)
    {
        $formattedAttachments = [];

        // PATHINFO_ALL | PATHINFO_DIRNAME | PATHINFO_BASENAME | PATHINFO_FILENAME | PATHINFO_EXTENSION
        if (is_string($attachments)) {
            // Single string input: Use the filename as the key and name
            $path       = $attachments;
            $extension  = pathinfo($path, PATHINFO_EXTENSION);
            $name       = pathinfo($path, PATHINFO_FILENAME);
            $formattedAttachments[$path] = Str::spaceReplacer($name) . ".{$extension}";
        } elseif (isset($attachments['path'])) {
            // Single array with 'path' and optional 'as'
            $path       = $attachments['path'];
            $extension  = pathinfo($path, PATHINFO_EXTENSION);
            $name       = empty($attachments['as']) 
                            ? pathinfo($path, PATHINFO_FILENAME)
                            : $attachments['as'];
            
            // formated name
            $formattedAttachments[$path] = Str::spaceReplacer($name) . ".{$extension}";
        } elseif (is_array($attachments)) {
            // Multiple attachments as an array of arrays
            foreach ($attachments as $attachment) {
                if (is_array($attachment) && isset($attachment['path'])) {
                    $path       = $attachment['path'];
                    $extension  = pathinfo($path, PATHINFO_EXTENSION);
                    $name       = empty($attachment['as']) 
                                    ? pathinfo($path, PATHINFO_FILENAME)
                                    : $attachment['as'];
                    
                    // formated name
                    $formattedAttachments[$path] = Str::spaceReplacer($name) . ".{$extension}";
                }
            }
        }

        return $formattedAttachments;
    }

    /**
     * SMTP Mailer Setup
     * @param array $options
     * 
     * @return void
     */
    private function setupMailer(?array $options = [])
    {
        // mailer isSMTP | IsMail
        $this->mailer->{$options['driver']}();

        // set to 1 or 2 to see the response from mail server
        $this->mailer->SMTPDebug = $options['debug']; 

        // prevent the SMTP session from being closed after each message
        $this->mailer->SMTPKeepAlive = $options['keep_alive']; 

        // Set timeout
        $this->mailer->Timeout = $options['timeout'];

        // trim port
        $this->smtpData['port'] = $this->smtpData['port'];

        //check mailer port type
        if((int) $this->smtpData['port'] === 465){
            // 465 | PHPMailer::ENCRYPTION_SMTPS - Enable SSL encryption
            $this->mailer->SMTPSecure =  PHPMailer::ENCRYPTION_SMTPS; 
        } else{
            // 587 - Enable TLS encryption
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
        }
        
        // Disable some SSL checks. 
        $this->mailer->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ],
            'tls' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ],
            'smtp' => [
                'timeout' => 30,
                'keepalive' => true,
                'pipelining' => true
            ]
        ];
        
        $this->mailer->SMTPAuth = $this->isSMTP();
        $this->mailer->CharSet  = 'UTF-8';
        $this->mailer->Username = $this->smtpData['username'];
        $this->mailer->Password = $this->smtpData['password'];
        $this->mailer->Host     = $this->smtpData['host'];
        $this->mailer->Port     = $this->smtpData['port']; 

        if(Tame()->emailValidator($this->smtpData['from_email'], false)){
            $this->mailer->setFrom($this->smtpData['from_email'], $this->smtpData['from_name']);
        }
    }
    
    /**
     * Checking if SMTP
     */
    private function isSMTP(): bool
    {
        return $this->driver === 'isSMTP';
    }
    
    /**
     * Checking if API Call
     */
    private function isAPI(): bool
    {
        return $this->driver === 'isAPI';
    }
    
    /**
     * Checking if Call is AWS
     */
    private function isAWS(): bool
    {
        return $this->provider === 'aws';
    }

    /**
     * Configure Driver to Method
     *
     * @param string $driver
     * @return string
     */
    private function configureDriver($driver)
    {
        $driver = Str::lower($driver);

        return match ($driver) {
            'mail', 'ismail' => 'isMail',
            'api', 'isapi' => 'isAPI',
            default => 'isSMTP'
        };
    }

    /**
     * Configure API Providers
     *
     * @param string $provider
     * @return string
     */
    private function configureProvider($provider)
    {
        $provider = Str::lower($provider);

        return match ($provider) {
            'sendgrid'   => 'sendgrid',
            'mailgun'    => 'mailgun',
            'mailjet'    => 'mailjet',
            'postmark'   => 'postmark',
            'aws'        => 'aws',
            'sparkpost'  => 'sparkpost',
            'brevo'      => 'brevo',
            default      => 'zeptomail',
        };
    }

    /**
     * Get default API endpoint for a provider.
     *
     * @param string $provider
     * @return string
     */
    private function getProviderApiUrl($provider)
    {
        $provider = Str::lower($provider);

        return match ($provider) {
            'sendgrid'   => 'https://api.sendgrid.com/v3/mail/send',
            'mailgun'    => 'https://api.mailgun.net/v3/YOUR_DOMAIN_NAME/messages',
            'mailjet'    => 'https://api.mailjet.com/v3.1/send',
            'postmark'   => 'https://api.postmarkapp.com/email',
            'aws'        => 'https://email.us-east-1.amazonaws.com',
            'sparkpost'  => 'https://api.sparkpost.com/api/v1/transmissions',
            'brevo'      => 'https://api.brevo.com/v3/smtp/email',
            'zeptomail'  => 'https://api.zeptomail.com/v1.1/email',
            default      => '', // return empty if unknown, user must provide
        };
    }

    /**
     * Get SMTP Data
     * 
     * @param array $options
     * @return array
     */
    public function getSMTPData()
    {
        return $this->smtpData;
    }

    /**
     * Get Default Options
     * 
     * @param array $options
     * @return array
     */
    private function getDefaultOption(?array $options = [])
    {
        $array = [
            'flush'         => $options['flush']        ?? $this->flushBuffering,
            'driver'        => $options['driver']       ?? $this->driver, 
            'debug'         => $options['debug']        ?? $this->debug, 
            'keep_alive'    => $options['keep_alive']   ?? $this->keepAlive, 
            'timeout'       => $options['timeout']      ?? $this->timeout,
        ];

        /**
         * Ensures that the 'debug' value in the $array array is valid.
         * Accepts only 0, 1, or 2 as valid debug levels; 
         * defaults to 0 if an invalid value is provided.
         */
        $array['debug'] = in_array($array['debug'], [0, 1, 2]) ? $array['debug'] : 0;
        
        return $array;
    }

    /**
     * Configure SMTP Data
     * @param array $options
     */
    private function configureSMTPData(?array $options = []): void
    {
        $this->smtpData = [
            'provider'      => $options['provider']     ?? env('MAIL_PROVIDER', ''),
            'driver'        => $options['driver']       ?? env('MAIL_DRIVER', ''),
            'host'          => $options['host']         ?? env('MAIL_HOST', ''),
            'port'          => $options['port']         ?? env('MAIL_PORT'),
            'username'      => $options['username']     ?? env('MAIL_USERNAME'),
            'password'      => $options['password']     ?? env('MAIL_PASSWORD'),
            'encryption'    => $options['encryption']   ?? env('MAIL_ENCRYPTION'),
            'from_email'    => $options['from_email']   ?? env('MAIL_FROM_ADDRESS'),
            'from_name'     => $options['from_name']    ?? env('MAIL_FROM_NAME'),
            'api_url'       => $options['api_url']      ?? env('MAIL_API_URL'),
            'api_token'     => $options['api_token']    ?? env('MAIL_API_TOKEN'),
            'api_secret'    => $options['api_secret']   ?? env('MAIL_API_SECRET'),
            'api_region'    => $options['api_region']   ?? env('MAIL_API_REGION'),
        ];

        // if driver is null
        if(empty($this->driver)){
            $this->driver = $this->configureDriver($this->smtpData['driver']);
        } else{
            if(empty($this->smtpData['driver'])){
                $this->smtpData['driver'] = $this->driver;
            }
        }

        // if provider is null
        if(empty($this->provider) && $this->isAPI()){
            $this->provider = $this->configureProvider($this->smtpData['provider']);
        } else{
            if(empty($this->smtpData['provider'])){
                $this->smtpData['provider'] = $this->provider;
            }
        }

        // if not APi and provider is set
        if(!empty($this->provider) && !$this->isAPI()){
            $this->provider = null;
        } else{
            if(empty($this->smtpData['api_url'])){
                $this->smtpData['api_url'] = $this->getProviderApiUrl($this->provider);
            }
        }
    }

    /**
     * Get Config
     */
    private static function getConfig(): array
    {
        return defined(self::$constantName) 
            ? constant(self::$constantName)
            : [];
    }

    /**
     * isMailInstance
     */
    private static function isMailInstance(): bool
    {
        return self::$staticData instanceof Mail;
    }

    /**
     * Handle the calls to non-existent methods.
     * 
     * @param string|null $method
     * @param mixed $args
     * @param mixed $clone
     * @return mixed
     */
    private static function nonExistMethod($method = null, $args = null, $clone = null) 
    {
        // convert to lowercase
        $name = Str::lower($method);

        // create correct method name
        $method = match ($name) {
            'altbody', 'altmessage' => '__altBody',
            'reply', 'replyto' => '__replyTo',
            default => '__altBody'
        };

        return $clone->$method(...$args);
    }

    /**
     * Creates a temporary email closure.
     *
     * This method allows you to define a callable that can be used to temporarily
     * modify or handle email-related logic. If no callable is provided, it will
     * use a default behavior.
     *
     * @param callable|null $callable An optional callable to customize the email handling.
     * @return mixed Returns the result of the callable or the default behavior.
     */
    private function createEmailTempClosure($callable = null)
    {
        $sendEmails = [];

        /**
         * Iterates over the list of 'to' recipients and performs actions for each email address.
         */
        foreach($this->recipients['to'] as $email){
            // Closure to hold emails of each recipient
            // Without executing immediately
            $sendEmails[] = function() use ($email, $callable) {
                try {

                    // Validate the recipient email
                    if (!Tame()->emailValidator($email, true)) {
                        throw new \Exception("Invalid email address: {$email}", 509); 
                    }

                    // If message body is empty
                    if (empty($this->body)) {
                        throw new \Exception("Email body cannot be empty.", 510);
                    }
                    
                    // email and name of receiver as name is optional
                    $this->mailer->addAddress($email);
                    
                    // add cc
                    $this->addCC();
                    
                    // add bcc
                    $this->addBCC();
        
                    // add reply to
                    $this->addReplyTo();
        
                    // Set email format to HTML
                    $this->mailer->isHTML(true); 
        
                    // subject
                    $this->mailer->Subject = $this->subject;
                    $this->mailer->Body    = $this->body;
                    
                    // If support alternative message
                    $this->addAltBody();

                    // Connect
                    $this->mailer->SMTPConnect();
        
                    // send mail
                    $this->mailer->send();
        
                    // get message id
                    $mid = $this->mailer->getLastMessageID();
        
                    // Clear previous addresses to avoid duplication
                    $this->mailer->clearAddresses();
                    
                    // Close the SMTP session
                    $this->mailer->SMTPClose();
                    
                    // if attachment delete is allowed
                    $this->deleteAttachment();
                    
                    // $this->mail->ErrorInfo
                    if(is_callable($callable)){
                        call_user_func($callable, (object) [
                            'status'    => 200, 
                            'message'   => 'Sent', 
                            'mid'       => $mid, 
                            'to'        => $email
                        ]);
                    }
                } catch (\Exception $e) {
                    if(is_callable($callable)){
                        call_user_func($callable, (object) [
                            'status'    => $e->getCode(), 
                            'message'   => $e->getMessage(), 
                            'mid'       => null, 
                            'to'        => $email
                        ]);
                    }
                }
            };
        }

        return $sendEmails;
    }

    /**
     * Creates a temporary email closure.
     *
     * This method allows you to define a callable that can be used to temporarily
     * modify or handle email-related logic. If no callable is provided, it will
     * use a default behavior.
     *
     * @param callable|null $callable An optional callable to customize the email handling.
     * @return mixed Returns the result of the callable or the default behavior.
     */
    private function createApiEmailTempClosure($callable = null)
    {
        $sendEmails = [];

        foreach ($this->recipients['to'] as $email) {
            $sendEmails[] = function() use ($email, $callable) {
                try {

                    $apiUrl = $this->smtpData['api_url'];
                    $apiToken = $this->smtpData['api_token'];

                    // setup error or missing
                    if(empty($apiUrl) || empty($apiToken)){
                        throw new \Exception(
                            sprintf("Missing Setup Data: MAIL_API_URL(%s) or MAIL_API_TOKEN(%s)", $apiUrl, $apiToken), 
                            508
                        );
                    }

                    // Validate the recipient email
                    if (!Tame()->emailValidator($email, true)) {
                        throw new \Exception("Invalid email address: {$email}", 509);
                    }
                    
                    // If message body is empty
                    if (empty($this->body)) {
                        throw new \Exception("Email body cannot be empty.", 510);
                    }

                    $fromEmail = $this->smtpData['from_email'];
                    
                    if (!Tame()->emailValidator($fromEmail, true)) {
                        throw new \Exception("Invalid From-Email address: {$fromEmail}", 511);
                    }

                    // Build Base Payload
                    $payload = $this->buildAPIPayload($email);

                    // If support alternative message
                    $this->addAltBody($payload, true);

                    // add cc
                    $this->addCC($payload, true);

                    // add bcc
                    $this->addBCC($payload, true);

                    // add reply to
                    $this->addReplyTo($payload, true);
                    
                    // Convert to Multipart Fields
                    $postFields = $this->buildPostfieldMultiparts($payload);

                    $this->attachPostfieldMultiparts($postFields, $payload);

                    // =======================
                    // AWS SES via SDK
                    // =======================
                    if ($this->isAWS()) {
                        $this->sendViaAWS($postFields, $callable, $email);
                    } else{
                        $this->sendViaCurl($apiUrl, $postFields, $callable, $email);
                    }
                } catch (\Exception $e) {
                    if(is_callable($callable)){
                        call_user_func($callable, (object)[
                            'status' => $e->getCode(),
                            'message' => $e->getMessage(),
                            'mid' => null,
                            'to' => $email
                        ]);
                    }
                }
            };
        }

        return $sendEmails;
    }
    
    /**
     * sendViaAWS
     *
     * @param  mixed $postFields
     * @param  mixed $callable
     * @param  mixed $email
     * @return void
     */
    private function sendViaAWS($postFields, $callable, $email)
    {
        $client = new \Aws\SesV2\SesV2Client([
            'version'     => 'latest',
            'region'      => $this->smtpData['api_region'],
            'credentials' => [
                'key'    => $this->smtpData['api_token'],
                'secret' => $this->smtpData['api_secret'],
            ],
        ]);

        // Build Destination
        $destination = [
            'ToAddresses' => [$email],
        ];

        if (!empty($this->recipients['cc'])) {
            $destination['CcAddresses'] = $this->recipients['cc'];
        }

        if (!empty($this->recipients['bcc'])) {
            $destination['BccAddresses'] = $this->recipients['bcc'];
        }

        // If attachments exist → RAW EMAIL
        if (!empty($this->attachments)) {

            $mime = $this->mailer;
            $mime->setFrom($this->smtpData['from_email'], $this->smtpData['from_name']);
            $mime->addAddress($email);

            if(!empty($destination['CcAddresses'])){
                foreach ($destination['CcAddresses'] ?? [] as $cc) {
                    $mime->addCC($cc);
                }
            }

            if(!empty($destination['BccAddresses'])){
                foreach ($destination['BccAddresses'] ?? [] as $bcc) {
                    $mime->addBCC($bcc);
                }
            }

            $replyTo    = $this->recipients['reply_to'];
            $address    = $replyTo[0] ?? null;
            $name       = $replyTo[1] ?? '';

            if (!empty($address)) {
                $mime->addReplyTo($address, $name);
            }

            $mime->isHTML(true);
            $mime->Subject = $this->subject;
            $mime->Body    = $this->body;

            if($this->altbody){
                $mime->AltBody = $this->altbody;
            }

            foreach ($this->attachments as $path => $name) {
                if (File::exists($path)) {
                    $mime->addAttachment($path, $name);
                }
            }

            $mime->preSend();

            $result = $client->sendEmail([
                'FromEmailAddress' => $this->smtpData['from_email'],
                'Destination'      => $destination,
                'Content' => [
                    'Raw' => [
                        'Data' => base64_encode($mime->getSentMIMEMessage())
                    ]
                ]
            ]);

        } else {

            // =========================
            // Simple Email
            // =========================

            dd(
                $this
            );

            $result = $client->sendEmail([
                'FromEmailAddress' => $this->smtpData['from_email'],
                'Destination'      => $destination,
                'Content' => [
                    'Simple' => [
                        'Subject' => [
                            'Data'    => $postFields['subject'] ?? '',
                            'Charset' => 'UTF-8',
                        ],
                        'Body' => [
                            'Html' => [
                                'Data'    => $postFields['htmlbody'] ?? '',
                                'Charset' => 'UTF-8',
                            ],
                            'Text' => [
                                'Data'    => $postFields['textbody'] ?? strip_tags($postFields['htmlbody'] ?? ''),
                                'Charset' => 'UTF-8',
                            ],
                        ],
                    ],
                ],
            ]);
        }

        $this->deleteAttachment();

        if (is_callable($callable)) {
            call_user_func($callable, (object)[
                'status' => 200,
                'message' => 'Sent via AWS SES SDK',
                'mid' => $result['MessageId'] ?? null,
                'to' => $email,
                'response' => $result
            ]);
        }
    }
    
    /**
     * Sending Email Via Curl-HTTP Request
     *
     * @param  mixed $apiUrl
     * @param  mixed $postFields
     * @param  mixed $callable
     * @param  mixed $email
     * @return void
     */
    private function sendViaCurl($apiUrl, $postFields, $callable, $email)
    {
        // using curl to send request
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($postFields),
            CURLOPT_HTTPHEADER => $this->getApiHeaders(),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        // if attachment delete is allowed
        $this->deleteAttachment();

        if($err){
            unset($curl);
            throw new \Exception($err, 500);
        }

        if(is_callable($callable)){
            call_user_func($callable, (object)[
                'status' => 200,
                'message' => 'Sent via API',
                'mid' => null,
                'to' => $email,
                'response' => $response
            ]);
        }
    }

    /**
     * Flushes output buffer and sends data to client.
     *
     * @param callable|null $callback The function to execute after flushing the buffer.
     * @param array|null $options The options to use during buffer flushing.
     *
     * @return void
     */
    private function ob_crons_flush(callable $callable, ?array $options = null)
    {
        // Disable output compression
        if (!headers_sent()) {
            @ini_set('zlib.output_compression', 'Off');
        }

        // Turn on implicit flushing
        ob_implicit_flush(true);

        // ignore user abort
        ignore_user_abort(true);

        // Disable script timeout
        set_time_limit(0);

        // turn on fast cgi
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        // Set the HTTP response code ... only available in > PHP 5.4.0
        http_response_code(200);

        // Start output buffering
        ob_start();

        // execute code block
        if(is_callable($callable)){
            // flush before calling the callable();
            $this->autoFlush($options);

            // call the method needed
            $callable();
        }
        
        // Disable implicit flushing
        ob_implicit_flush(false);
    }

    /**
     * Flushes output buffers and sends headers to enable server-side flushing.
     * Used internally by ob_crons_flush to ensure all buffers are sent and closed.
     * 
     * @param array $options Options to configure flush behavior
     * @return void
     */
    private function autoFlush(?array $options = [])
    {
        // If flush is enabled and not in debug mode, set headers for streaming
        if ($options['flush'] && $options['debug'] === 0) {
            if (!headers_sent()) {
                @header('Surrogate-Control: BigPipe/1.0');
                @header('X-Accel-Buffering: no');
                @header("Content-Encoding: none");  
                @header("Connection: close");
                @header("Content-Length: " . ob_get_length());
            }
        }

        // Flush and end all output buffers if active
        if (ob_get_level() > 0) {
            flush();
            ob_flush();
            ob_end_flush();
        }

        // Clean up any remaining buffers if active
        if (ob_get_level() > 0) {
            ob_clean();
            ob_end_clean();
        }

        // Enable implicit flush for real-time output
        ob_implicit_flush(true);
    }
    
}