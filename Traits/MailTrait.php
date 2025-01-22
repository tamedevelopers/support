<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Traits;

use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Mail;
use PHPMailer\PHPMailer\PHPMailer;



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
        'to'    => [],
        'cc'    => false,
        'bcc'   => false,
        'reply_to'   => false,
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
    static private $constantName = 'TAME_MAILER_CONFIG___';
    
    /**
     * static
     *
     * @var mixed
     */
    static private $staticData;
    

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

        return$data[$mode] ?? $data;
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
                @unlink($path);
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
            $formattedAttachments[$path] = "{$name}.{$extension}";
        } elseif (isset($attachments['path'])) {
            // Single array with 'path' and optional 'as'
            $path       = $attachments['path'];
            $extension  = pathinfo($path, PATHINFO_EXTENSION);
            $name       = $attachments['as'] ?? pathinfo($path, PATHINFO_FILENAME);
            $formattedAttachments[$path] = "{$name}.{$extension}";
        } elseif (is_array($attachments)) {
            // Multiple attachments as an array of arrays
            foreach ($attachments as $attachment) {
                if (is_array($attachment) && isset($attachment['path'])) {
                    $path       = $attachment['path'];
                    $extension  = pathinfo($path, PATHINFO_EXTENSION);
                    $name       = $attachment['as'] ?? pathinfo($path, PATHINFO_FILENAME);
                    $formattedAttachments[$path] = "{$name}.{$extension}";
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

        //set to 1 or 2 to see the response from mail server
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
     * isSMTP
     *
     * @return bool
     */
    private function isSMTP()
    {
        return $this->driver === 'isSMTP';
    }

    /**
     * Create Default Options for Mailer
     * @param array $options
     * 
     * @return array
     */
    private function getDefaultOption(?array $options = [])
    {
        return [
            'flush'         => $options['flush']        ?? $this->flushBuffering,
            'driver'        => $options['driver']       ?? $this->driver, 
            'debug'         => $options['debug']        ?? $this->debug, 
            'keep_alive'    => $options['keep_alive']   ?? $this->keepAlive, 
            'timeout'       => $options['timeout']      ?? $this->timeout,
        ];
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
    static private function getConfig()
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
    static private function isMailInstance()
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
    static private function nonExistMethod($method = null, $args = null, $clone = null) 
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
    
}