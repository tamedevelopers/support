<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Traits;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use Tamedevelopers\Support\Capsule\File;
use Tamedevelopers\Support\Mail;
use Tamedevelopers\Support\Process\HttpRequest;
use Tamedevelopers\Support\Server;
use Tamedevelopers\Support\Str;


trait MailTrait{

    /**
     * The PHPMailer instance.
     * @var PHPMailer
     */
    private $mailer;

    /**
     * List of message recipients.
     * @var array{to: array, cc: mixed, bcc: mixed, reply_to: mixed}
     */
    private $recipients = [
        'to'  => [],
        'cc'  => false,
        'bcc' => false,
        'reply_to' => false,
    ];

    /**
     * Custom mailer options.
     * @var array
     */
    private $options = [];

    /**
     * Resolved SMTP configuration data.
     * @var array
     */
    private $smtpData = [];

    /**
     * Collection of attachments to be sent.
     * @var array
     */
    private $attachments = [];

    /**
     * Whether to delete attachments from the server after sending.
     * @var bool
     */
    private $deleteAttachment = false;

    /**
     * Whether to enable automatic output buffer flushing.
     * @var bool
     */
    private $flushBuffering = false;

    /**
     * Queued callbacks for direct browser submit mode.
     * @var array<int, callable>
     */
    private $directFlushQueue = [];

    /**
     * Ensure direct flush queue worker is registered once.
     * @var bool
     */
    private $directFlushRegistered = false;

    /**
     * Ensure browser response is detached once per request.
     * @var bool
     */
    private $directResponseDetached = false;
    
    /**
     * The resolved "from" address and name.
     * @var array{email: string|null, name: string}
     */
    private $from = [];
    
    /**
     * The mail configuration settings.
     * @var array
     */
    private $config = [];
    
    /**
     * Static transport driver name.
     * @var string|null
     */
    private static $staticTransport = null;
    
    /**
     * The active mail transport driver.
     * @var string|null
     */
    private $transport = null;
    
    /**
     * SMTP debug level (0, 1, or 2).
     * @var int
     */
    private $debug = 0;
    
    /**
     * Connection timeout in seconds.
     * @var int
     */
    private $timeout = 10;
    
    /**
     * Whether to use persistent SMTP connections.
     * @var bool
     */
    private $keepAlive = true;
    
    /**
     * The email subject line.
     * @var string
     */
    private $subject;
    
    /**
     * The primary email body (HTML).
     * @var string
     */
    private $body;
    
    /**
     * The plain-text alternative email body.
     * @var string|bool
     */
    private $altbody = false;
    
    /**
     * The constant name used for static configuration.
     * @var string
     */
    private static $constantName = 'TAME_MAILER_CONFIG___';
    
    /**
     * Static data cache for Mail instances.
     * @var mixed
     */
    private static $staticData;

    /**
     * Convert input to an array of valid email addresses.
     *
     * @param  string|array|null  $emails
     * @param  string|null  $mode  Optional return key (email|count).
     * @return array|int
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
     * Set the Reply-To address fluently.
     *
     * @param  string  ...$emails
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
     * Attach CC recipients to the mailer or API payload.
     * @param  array  $payload
     * @param  bool  $isapi
     */
    private function addCC(&$payload = [], $isapi = false): void
    {
        if(!empty($this->recipients['cc'])){
            foreach($this->recipients['cc'] as $cc){
                if(Tame()->emailValidator($cc, false)){
                    if(!$isapi){
                        $this->mailer->addCC($cc);
                    } else{
                        switch($this->transport){
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
                            case 'brevo':
                                $payload['cc'][] = ['email' => $cc];
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
     * Attach BCC recipients to the mailer or API payload.
     * 
     * @param  array  $payload
     * @param  bool  $isapi
     */
    private function addBCC(&$payload = [], $isapi = false): void
    {
        if(!empty($this->recipients['bcc'])){
            foreach($this->recipients['bcc'] as $bcc){
                if(Tame()->emailValidator($bcc, false)){
                    if(!$isapi){
                        $this->mailer->addBCC($bcc);
                    } else{
                        switch($this->transport){
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
                            case 'brevo':
                                $payload['bcc'][] = ['email' => $bcc];
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
     * Attach Reply-To address to the mailer or API payload.
     * @param  array  $payload
     * @param  bool  $isapi
     */
    private function addReplyTo(&$payload = [], $isapi = false): void
    {
        $replyTo    = $this->recipients['reply_to'];
        $address    = $replyTo[0] ?? null;
        $name       = $replyTo[1] ?? '';

        if(!empty($replyTo) && !empty($address)){
            if(!$isapi){
                $this->mailer->addReplyTo($address, $name);
            } else{
                switch($this->transport){
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
     * Add AltBody to API payload if supported by transport.
     * @param  array  $payload
     * @param  bool  $isapi
     */
    private function addAltBody(&$payload = [], $isapi = false): void
    {
        // If support alternative message
        if(!empty($this->altbody)){
            if(!$isapi){
                $this->mailer->AltBody = $this->altbody; 
            } else{
                switch($this->transport){
                    case 'sendgrid':
                        $payload['content'][] = ['type' => 'text/plain', 'value' => $this->altbody];
                        break;
                    case 'mailgun':
                        $payload['text'] = $this->altbody;
                        break;
                    case 'brevo':
                        $payload['textContent'] = $this->altbody;
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
     * Delete processed attachments from the local file system.
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
     * Configure the PHPMailer instance for SMTP transport.
     * @param  array|null  $options
     */
    private function setupMailer(?array $options = []): void
    {
        // mailer smtp
        $this->mailer->isSMTP();

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
        
        $this->mailer->SMTPAuth = true;
        $this->mailer->CharSet  = 'UTF-8';
        $this->mailer->Username = $this->smtpData['username'];
        $this->mailer->Password = $this->smtpData['password'];
        $this->mailer->Host     = $this->smtpData['host'];
        $this->mailer->Port     = $this->smtpData['port']; 
    }

    /**
     * Resolve and validate the requested API transport driver.
     *
     * @param  string  $transport
     * @return string
     */
    private function configureTransport($transport)
    {
        $transport = Str::lower($transport);
            
        return match ($transport) {
            'sendgrid'   => 'sendgrid',
            'mailgun'    => 'mailgun',
            'mailjet'    => 'mailjet',
            'postmark'   => 'postmark',
            'ses'        => 'ses',
            'mailchimp'  => 'mailchimp',
            'elastic'    => 'elastic',
            'brevo'      => 'brevo',
            'zeptomail'  => 'zeptomail',
            'socketlabs' => 'socketlabs',
            default      => 'smtp',
        };
    }

    /**
     * Get the default API endpoint for a specific provider.
     *
     * @param  string  $transport
     * @return string
     */
    private function getTransportApiUrl($transport)
    {
        $transport  = Str::lower($transport);
        $domain     = $this->resolveHostNameFromEmail();

        // mailgun requires domain in the API endpoint, 
        // so we replace the placeholder with the actual domain
        $mailgunProtocol = Str::replace(
            '[YOUR_DOMAIN_NAME]', 
            $domain, 
            'https://api.mailgun.net/v3/[YOUR_DOMAIN_NAME]/messages'
        );

        return match ($transport) {
            'sendgrid'   => 'https://api.sendgrid.com/v3/mail/send',
            'mailgun'    =>  $mailgunProtocol,
            'mailjet'    => 'https://api.mailjet.com/v3.1/send',
            'brevo'      => 'https://api.brevo.com/v3/smtp/email',
            'postmark'   => 'https://api.postmarkapp.com/email',
            'ses'        => 'https://email.us-east-1.amazonaws.com',
            'zeptomail'  => 'https://api.zeptomail.com/v1.1/email',
            'mailchimp'  => 'https://mandrillapp.com/api/1.0/messages/send.json',
            'elastic'    => 'https://api.elasticemail.com/v4/emails/transactional',
            'socketlabs' => 'https://api.socketlabs.com/v1/email',
            default      => '', // return empty if unknown, user must provide
        };
    }

    /**
     * Retrieve the current SMTP configuration data.
     */
    public function getSMTPData(): array
    {
        return $this->smtpData;
    }

    /**
     * Resolve default mailer options, ensuring valid debug levels.
     * @param  array|null  $options
     * @return array
     */
    private function getDefaultOption(?array $options = [])
    {
        $array = [
            'flush'         => $options['flush']        ?? $this->flushBuffering,
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
     * Resolve the sender "from" address and name based on configuration priority.
     *
     * @param  array  $mailConfig
     * @param  array  $finalConfig
     */
    private function resolveFromAddress($mailConfig, $finalConfig): void
    {
        if(isset($finalConfig['from.address']) || isset($finalConfig['from']['address'])){
            $address = $finalConfig['from.address'] ?? $finalConfig['from']['address'];
        }
        if(isset($finalConfig['from.name']) || isset($finalConfig['from']['name'])){
            $name = $finalConfig['from.name'] ?? $finalConfig['from']['name'];
        }

        // Priority: Fluent $this->from > 
        $fromAddress = $this->from['address'] ?? $address
            ?? $mailConfig['from']['address']
            ?? null;

        $fromName = $this->from['name'] ?? $name
            ?? $mailConfig['from']['name'] 
            ?? 'Mailer';

        $this->from = [
            'address' => $fromAddress,
            'name'  => $fromName
        ];
    }

    /**
     * Extract the domain/hostname from the current "from" email address.
     */
    private function resolveHostNameFromEmail(): string
    {
        $hosts = explode('@', $this->from['address'] ?? '');
        
        return $hosts[1] ?? '';
    }

    /**
     * Consolidate and resolve SMTP configuration data from all sources.
     * 
     * @param array|null $globalRuntime (Mail::config)
     */
    private function configureSMTPData(?array $globalRuntime = []): void
    {
        // Pull base defaults from config/mail.php
        $mailConfig = config('mail');
        
        // mail fallback key
        $defaultKey = 'smtp';
        
        // Priority Logic: (Priority: Instance > Global Runtime > Config File)
        // We treat 'this->transport' as the source of truth if it was set via fluent method
        $this->transport = $this->configureTransport(
            $this->transport ?? $globalRuntime['transport'] ?? $mailConfig['default'] ?? $defaultKey
        );
        
        // If no data found, then we assume the data does'nt exists
        // inside of the mail.mailers.$providerKey[data], we revert back to default
        $defaultMailerData = config("mail.mailers.{$this->transport}") ?? config("mail.mailers.{$defaultKey}");

        // should be changed by setters
        $setterConfig = Server::config('mail');
        $settersValue = isset($setterConfig['transport']) ? $setterConfig : [];

        // Merge data correctly
        $finalConfig = array_merge(
            $defaultMailerData, 
            $globalRuntime,
            $settersValue
        );

        // Map to internal smtpData property
        $this->smtpData = [
            'transport'  => $this->transport,
            'host'       => $finalConfig['host'] ?? null,
            'port'       => $finalConfig['port'] ?? 587,
            'username'   => $finalConfig['username'] ?? null,
            'password'   => $finalConfig['password'] ?? null,
            'encryption' => $finalConfig['encryption'] ?? 'tls',
            'url'        => $finalConfig['url'] ?? null,
            'token'      => $finalConfig['token'] ?? null,
            'secret'     => $finalConfig['secret'] ?? null,
            'region'     => $finalConfig['region'] ?? null,
        ];

        // Handle "From" Address Priority
        $this->resolveFromAddress($mailConfig, $finalConfig);

        // Configure url if an api and url is empty
        if($this->isAPI() && empty($this->smtpData['url'])){
            $this->smtpData['url'] = $this->getTransportApiUrl($this->transport);
        }
    }

    /**
     * Retrieve static configuration from defined constants.
     */
    private static function getConfig(): array
    {
        return defined(self::$constantName) 
            ? constant(self::$constantName)
            : [];
    }

    /**
     * Determine if static data is an instance of Mail.
     */
    private static function isMailInstance(): bool
    {
        return self::$staticData instanceof Mail;
    }

    /**
     * Standardize attachments into a structured associative array.
     *
     * @param  mixed  $attachments  Path string, single array, or collection array.
     * @return array  Associative array with file paths as keys and formatted names as values.
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
        // Keep legacy behavior for ajax/fetch/api where autoFlush already works well.
        if ($this->isAsyncLikeRequest()) {
            if (is_callable($callable)) {
                $this->autoFlush($options);
                $callable();
            }

            return;
        }

        // Direct browser submit:
        // 1) detach response once so browser can complete immediately
        // 2) run queued callbacks during shutdown
        $this->directFlushQueue[] = $callable;

        if (!$this->directResponseDetached) {
            $this->detachDirectBrowserResponse($options);
        }

        if ($this->directFlushRegistered) {
            return;
        }

        $this->directFlushRegistered = true;
        register_shutdown_function(function () {
            $this->runDirectFlushQueue();
        });
    }

    /**
     * Heuristic check for ajax/fetch/api style requests.
     *
     * @return bool
     */
    private function isAsyncLikeRequest(): bool
    {
        if (HttpRequest::runningInConsole()) {
            return false;
        }

        if (HttpRequest::isAjax()) {
            return true;
        }

        $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
        $uri = strtolower((string) ($_SERVER['REQUEST_URI'] ?? ''));

        if (str_contains($accept, 'application/json')) {
            return true;
        }

        if (str_contains($contentType, 'application/json')) {
            return true;
        }

        return str_contains($uri, '/api/');
    }

    /**
     * Close browser connection for direct submit requests.
     *
     * @param array|null $options
     * @return void
     */
    private function detachDirectBrowserResponse(?array $options = null): void
    {
        $this->directResponseDetached = true;

        ignore_user_abort(true);
        @set_time_limit(0);

        if (function_exists('session_status') && session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }

        if (function_exists('fastcgi_finish_request')) {
            @fastcgi_finish_request();
            return;
        }

        $this->autoFlush($options);
    }

    /**
     * Execute direct submit queued callbacks after response detach.
     *
     * @return void
     */
    private function runDirectFlushQueue(): void
    {
        foreach ($this->directFlushQueue as $callback) {
            if (!is_callable($callback)) {
                continue;
            }

            try {
                call_user_func($callback);
            } catch (\Throwable $e) {
                continue;
            }
        }

        $this->directFlushQueue = [];
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
        $options = is_array($options) ? $options : [];
        $flushEnabled = (bool) ($options['flush'] ?? false);
        $debugLevel = (int) ($options['debug'] ?? 0);

        // If flush is enabled and not in debug mode, set headers for streaming
        if ($flushEnabled && $debugLevel === 0) {
            if (!headers_sent()) {
                @header('Surrogate-Control: BigPipe/1.0');
                @header('X-Accel-Buffering: no');
                @header("Content-Encoding: none");  
                @header("Connection: close");

                $length = ob_get_length();
                if ($length !== false && $length > 0) {
                    @header("Content-Length: " . $length);
                }
            }
        }

        // Flush and end all output buffers safely.
        while (ob_get_level() > 0) {
            @ob_end_flush();
        }

        // Enable implicit flush for real-time output
        ob_implicit_flush(true);
        @flush();
    }
    
}