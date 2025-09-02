<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Traits;

use PHPMailer\PHPMailer\SMTP;
use Tamedevelopers\Support\Env;
use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Mail;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Tamedevelopers\Support\Capsule\File;


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
    private $driver = 'isSMTP';
    
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
        $emailArray = is_array($emails) ? $emails : explode(',', str_replace(["\r", "\n", " "], "", $emails));

        $emailArray = Str::flattenValue($emailArray);

        // Filter and validate email addresses
        $validEmails = array_filter($emailArray, function ($email) {
            return filter_var(trim($email), FILTER_VALIDATE_EMAIL);
        });

        // Return the array of valid email addresses and their count
        $data = [
            "email" => array_values($validEmails), // Reset array keys
            "count" => count($validEmails)
        ];

        return $data[$mode] ?? $data;
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
     * @return void
     */
    private function addCC()
    {
        if(!empty($this->recipients['cc'])){
            foreach($this->recipients['cc'] as $cc){
                if(Tame()->emailValidator($cc, false, false)){
                    $this->mailer->addCC($cc);
                }
            }
        }
    }
    
    /**
     * addBCC
     *
     * @return void
     */
    private function addBCC()
    {
        if(!empty($this->recipients['bcc'])){
            foreach($this->recipients['bcc'] as $bcc){
                if(Tame()->emailValidator($bcc, false, false)){
                    $this->mailer->addBCC($bcc);
                }
            }
        }
    }
    
    /**
     * addReplyTo
     *
     * @return void
     */
    private function addReplyTo()
    {
        $replyTo = $this->recipients['reply_to'];

        if(!empty($replyTo)){
            $this->mailer->addReplyTo($replyTo[0], $replyTo[1]);
        }
    }

    /**
     * Delete attachment
     * @return void
     */
    private function deleteAttachment()
    {
        // if attachment delete is allowed
        if($this->deleteAttachment){
            foreach($this->attachments as $path => $name){
                File::delete($path);
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

        if(Tame()->emailValidator($this->smtpData['from_email'], false, false)){
            $this->mailer->setFrom($this->smtpData['from_email'], $this->smtpData['from_name']);
        }
    }
    
    /**
     * Ensures that the environment is started if it has not been initialized yet.
     *
     * This method checks the current state of the environment and performs
     * initialization steps if necessary to guarantee that the environment is ready
     * for further operations.
     *
     * @return void
     */
    private function startEnvIFNotStarted()
    {
        // if ENV has not been started
        if(!Env::isEnvStarted()){
            Env::createOrIgnore();
            Env::load();
        }
    }
    
    /**
     * isSMTP
     *
     * @return bool
     */
    private function isSMTP()
    {
        return $this->driver === 'isSMTP';
    }

    /**
     * Get SMTP Data
     * @param array $options
     * 
     * @return array
     */
    public function getSMTPData()
    {
        return $this->smtpData;
    }

    /**
     * Get Default Options
     * @param array $options
     * 
     * @return array
     */
    private function getDefaultOption(?array $options = [])
    {
        $data = [
            'flush'         => $options['flush']        ?? $this->flushBuffering,
            'driver'        => $options['driver']       ?? $this->driver, 
            'debug'         => $options['debug']        ?? $this->debug, 
            'keep_alive'    => $options['keep_alive']   ?? $this->keepAlive, 
            'timeout'       => $options['timeout']      ?? $this->timeout,
        ];

        /**
         * Ensures that the 'debug' value in the $data array is valid.
         * Accepts only 0, 1, or 2 as valid debug levels; 
         * defaults to 0 if an invalid value is provided.
         */
        $data['debug'] = in_array($data['debug'], [0, 1, 2]) ? $data['debug'] : 0;
        
        return $data;
    }

    /**
     * Configure SMTP Data
     * @param array $options
     * 
     * @return void
     */
    private function configureSMTPData(?array $options = [])
    {
        $this->smtpData = [
            'host'          => $options['host']         ?? env('MAIL_HOST', ''),
            'port'          => $options['port']         ?? env('MAIL_PORT'),
            'username'      => $options['username']     ?? env('MAIL_USERNAME'),
            'password'      => $options['password']     ?? env('MAIL_PASSWORD'),
            'encryption'    => $options['encryption']   ?? env('MAIL_ENCRYPTION'),
            'from_email'    => $options['from_email']   ?? env('MAIL_FROM_ADDRESS'),
            'from_name'     => $options['from_name']    ?? env('MAIL_FROM_NAME'),
        ];
    }

    /**
     * Get Config
     *
     * @return array
     */
    private static function getConfig()
    {
        return defined(self::$constantName) 
            ? constant(self::$constantName)
            : [];
    }

    /**
     * isMailInstance
     *
     * @return bool
     */
    private static function isMailInstance()
    {
        return self::$staticData instanceof Mail;
    }

    /**
     * Handle the calls to non-existent methods.
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
                    $verify = Tame()->emailValidator($email, true, true);
                    if (!$verify) {
                        // Custom error code: 509
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
                    if(!empty($this->altbody)){
                        $this->mailer->AltBody = $this->altbody; 
                    }
        
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