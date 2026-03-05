<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Traits;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\PHPMailer;
use Tamedevelopers\Support\Capsule\File;
use Tamedevelopers\Support\Mail;
use Tamedevelopers\Support\Str;


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
     * Add CC recipients.
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
                            case 'postmark':
                                $payload['Cc'][] = $cc;
                                break;
                            case 'mailgun':
                                $payload['cc'][] = $cc;
                                break;
                            case 'mailchimp':
                                $payload['message']['to'][] = [
                                    'email' => $cc,
                                    'type'  => 'cc'
                                ];
                                break;
                            case 'socketlabs':
                                $payload['Messages'][0]['Cc'][] = ['EmailAddress' => $cc];
                                break;
                            case 'elastic':
                                $payload['Recipients']['CC'][] = $cc;
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
     * Add BCC recipients.
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
                            case 'postmark':
                                $payload['Bcc'][] = $bcc;
                                break;
                            case 'mailgun':
                                $payload['bcc'][] = $bcc;
                                break;
                            case 'mailchimp':
                                $payload['message']['to'][] = [
                                    'email' => $bcc,
                                    'type'  => 'bcc'
                                ];
                                break;
                            case 'socketlabs':
                                $payload['Messages'][0]['Bcc'][] = [
                                    'EmailAddress' => $bcc
                                ];
                                break;
                            case 'elastic':
                                $payload['Recipients']['BCC'][] = $bcc;
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
     * Add Reply-To recipient.
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
                    case 'postmark':
                        $payload['ReplyTo'] = $address;
                        break;
                    case 'mailgun':
                        $payload['h:Reply-To'] = $address;
                        break;
                    case 'mailchimp':
                        $payload['message']['headers']['Reply-To'] = $address;
                        break;
                    case 'socketlabs':
                        $payload['Messages'][0]['ReplyTo'] = [
                            'EmailAddress' => $address,
                            'FriendlyName' => $name
                        ];
                        break;
                    case 'elastic':
                        $payload['Content']['replyTo'] = "{$name} <{$address}>";
                        break;
                    default:
                        $payload['reply_to'] = ['address' => $address, 'name' => $name];
                        break;
                }
            }
        }
    }
    
    /**
     * Add AltBody to payload if supported by provider.
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
                        $payload['content'][] = ['type' => 'text/plain', 'value' => $this->altbody];
                        break;
                    case 'mailgun':
                        $payload['text'] = $this->altbody;
                        break;
                    case 'postmark':
                        $payload['TextBody'] = $this->altbody;
                        break;
                    case 'mailjet':
                        $payload['TextPart'] = $this->altbody;
                        break;
                    case 'mailchimp':
                        $payload['message']['text'] = $this->altbody;
                        break;
                    case 'socketlabs':
                        $payload['Messages'][0]['TextBody'] = $this->altbody;
                        break;
                    case 'elastic':
                        $payload['Content']['Body'][] = [
                            "ContentType" => "PlainText",
                            "Content" => $this->altbody
                        ];
                        break;
                    default:
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
            'mailchimp'  => 'mailchimp',
            'elastic'    => 'elastic',
            'socketlabs', 'socketlab'  => 'socketlabs',
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

        $hosts = explode('@', $this->smtpData['from_email']);
        $domain = $hosts[1] ?? '';

        // mailgun requires domain in the API endpoint, 
        // so we replace the placeholder with the actual domain
        $mailgun = Str::replace(
            'YOUR_DOMAIN_NAME', 
            $domain, 
            'https://api.mailgun.net/v3/YOUR_DOMAIN_NAME/messages'
        );

        return match ($provider) {
            'sendgrid'   => 'https://api.sendgrid.com/v3/mail/send',
            'mailgun'    => $mailgun,
            'mailjet'    => 'https://api.mailjet.com/v3.1/send',
            'postmark'   => 'https://api.postmarkapp.com/email',
            'aws'        => 'https://email.us-east-1.amazonaws.com',
            'zeptomail'  => 'https://api.zeptomail.com/v1.1/email',
            'mailchimp'  => 'https://mandrillapp.com/api/1.0/messages/send.json',
            'elastic'  => 'https://api.elasticemail.com/v4/emails/transactional',
            'socketlabs', 'socketlab'  => 'https://api.socketlabs.com/v1/email',
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