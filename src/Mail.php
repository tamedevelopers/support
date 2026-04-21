<?php

namespace Tamedevelopers\Support;

use PHPMailer\PHPMailer\PHPMailer;
use Tamedevelopers\Support\Capsule\File;
use Tamedevelopers\Support\Capsule\Manager;
use Tamedevelopers\Support\Traits\MailApiTransport;
use Tamedevelopers\Support\Traits\MailSMTPTransport;
use Tamedevelopers\Support\Traits\MailTrait;
        
/**
 * Mailer with dynamic fluent methods via __call.
 *
 * Magic methods documented for static analysis:
 * @method $this altBody(string $body)
 * @method $this altMessage(string $body)
 * @method $this reply(string $address, string|null $name = null)
 * @method $this replyTo(string $address, string|null $name = null)
 */
class Mail{

    use MailTrait,
        MailApiTransport,
        MailSMTPTransport;

    /**
     * Constructor method to initialize the PHPMailer object
     * @param string|array|null $emails
     * 
     * @return void
     */
    public function __construct($emails = null, ?array $options = [])
    {
        $this->mailer = new PHPMailer(true);

        Manager::startEnvIFNotStarted();

        if(!empty($emails)){
            $this->recipients['to'] = $this->convert($emails, 'email');
        }

        if(!empty($options)){
            $this->options = $options;
        }

        // clone copy of self
        if(!self::isMailInstance()){
            self::$staticData = clone $this;
        }
    }

    /**
     * Handle the calls to non-existent instance methods.
     * @param string $name
     * @param mixed $args
     * 
     * @return mixed
     */
    public function __call($name, $args) 
    {
        return self::nonExistMethod($name, $args, $this);
    }

    /**
     * Set manual configuration
     *
     * @param array $options Mailer configuration options
     * - (transport|host|port|username|password|encryption|from_email|from_name|url|token|secret|region)
     * 
     * @return $this
     */
    public static function config(?array $options = [])
    {
        if(!defined(self::$constantName)){
            define(self::$constantName, $options);
        }

        return new self([], []);
    }

    /**
     * Set the recipient(s) of the email.
     *
     * @param string|array $emails
     * @return $this
     */
    public static function to(...$emails)
    {
        if(func_num_args() === 1){
            if(isset($emails[0]) && is_string($emails[0])){
                $emails = explode(',', str_replace(["\r", "\n", " "], "", $emails[0]));
            }
        }

        // new class instance
        $instance = new self(
            $emails,
            self::getConfig()
        );

        // automatically collecting the transport data if set or method 
        // used before calling the to()
        if(!empty(self::$staticTransport)){
            $instance->transport    = self::$staticTransport;
            self::$staticTransport  = null;
        }

        return $instance;
    }

    /**
     * Add CC recipients.
     *
     * @param string|array $emails
     * @return $this
     */
    public function cc(...$emails)
    {
        $emails = Str::flatten($emails);

        $this->recipients['cc'] = $this->convert($emails, 'email');

        return $this;
    }

    /**
     * Add BCC recipients.
     *
     * @param string|array $emails
     * @return $this
     */
    public function bcc(...$emails)
    {
        $emails = Str::flatten($emails);
        
        $this->recipients['bcc'] = $this->convert($emails, 'email');

        return $this;
    }

    /**
     * Set the from data.
     *
     * @param array $subject
     * @return $this
     */
    public function from($from)
    {
        if(empty($from['address'])){
            throw new \Exception("Email address field is required: ['address' => 'email@example.com']", 508);
        }

        if (!Tame()->emailValidator($from['address'], false)) {
            throw new \Exception("Invalid email address: {$from['address']}", 509);
        }

        // passed email
        $this->from['address'] = $from['address'];

        if(!empty($from['name'])){
            $this->from['name'] = $from['name'];
        }

        return $this;
    }

    /**
     * Set the email subject.
     *
     * @param string $subject
     * @return $this
     */
    public function subject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Set the email body.
     *
     * @param string $body
     * @return $this
     */
    public function body($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Set the email sending Transport.
     *
     * @param string $transport
     * - [optional] Default is smtp (zeptomail, sendgrid, mailgun, mailjet, postmark, ses, mailchimp, socketlabs, elastic, brevo)
     * 
     * @return $this
     */
    public function transport($transport = 'smtp')
    {
        $this->transport = $transport;

        self::$staticTransport = $this->transport;

        return $this;
    }

    /**
     * Add attachments to the email.
     *
     * @param string|array $attachments
     * @return $this
     */
    public function attach(...$attachments)
    {
        $args = func_num_args();

        // Normalize attachments input
        if($args === 1 || $args === 2){
            if($args === 1){
                $path = $attachments[0];
                $as = pathinfo($attachments[0], PATHINFO_FILENAME);
            } else{
                $filePath = File::isFileType($attachments[0]);
                $filePath2 = File::isFileType($attachments[1]);

                if($filePath){
                    $path = $attachments[0];
                    $as = $attachments[1];
                } elseif($filePath2){
                    $path = $attachments[1];
                    $as = $attachments[0];
                }   else{       
                    $path = $attachments[0];
                    $as = $attachments[1];
                }
            }
            
            $attachments = ['path' => $path, 'as' => basename($as)];
        }

        $this->attachments = $this->formatAttachments($attachments);

        foreach ($this->attachments as $path => $name) {
            if(File::exists($path)){
                $this->mailer->addAttachment($path, $name);
            }
        }

        return $this;
    }

    /**
     * Delete attachments from server after mail has been sent
     *
     * @param bool $delete
     * @return $this
     */
    public function delete($delete)
    {
        $this->deleteAttachment = $delete;

        return $this;
    }

    /**
     * Flush Buffering from the server to avoid waiting for mail response before reload
     *
     * @param bool $flush
     * @return $this
     */
    public function flush($flush)
    {
        $this->flushBuffering = $flush;

        return $this;
    }

    /**
     * Debug error code for development purpose only
     *
     * @param int $debug
     * @return $this
     */
    public function debug($debug)
    {
        $this->debug = $debug;

        return $this;
    }

    /**
     * Proceed sending email
     * 
     * @param callable $callable
     * @return void
     */
    public function send($callable = null)
    {
        // configure smtp data
        $this->configureSMTPData($this->options);

        // create default options
        $defaultOption = $this->getDefaultOption($this->options);

        // define once
        $isApi = $this->isAPI();

        // setup mailer if only the driver is not an API
        if(!$isApi){
            $this->setupMailer($defaultOption);
        }

        // create email closures
        if($isApi){
            $sendEmails = $this->createApiEmailTempClosure($callable);
        } else{
            $sendEmails = $this->createEmailTempClosure($callable);
        }

        TameCollect($sendEmails)
            ->each(function($fn) use ($defaultOption) {
                // If flushBuffering is enabled, release response and send in background
                if ($this->flushBuffering) {
                    $this->ob_crons_flush($fn, $defaultOption);
                } else{
                    $fn();
                }
            });

        // Final SMTP Cleanup after the collection is done
        if (!$isApi) {
            $this->mailer->SMTPClose();
        }
    }
    
    /**
     * Close HTTP connection and continue script execution.
     * 
     * Flushes all output buffers, sends appropriate headers, and closes the
     * connection to the client while allowing the script to run in the background.
     * Uses FastCGI optimization when available.
     */
    public function obFlush(): void
    {
        @ignore_user_abort(true);
        @set_time_limit(0);

        if (function_exists('session_status') && session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }

        // Prefer FastCGI native request finalization when available.
        if (function_exists('fastcgi_finish_request')) {
            @fastcgi_finish_request();
            return;
        }

        if (!headers_sent()) {
            @header('Connection: close');
            @header('Content-Encoding: none');
            @header('X-Accel-Buffering: no');

            // Get the length of the output buffer
            $length = ob_get_length();
            if ($length !== false && $length > 0) {
                @header('Content-Length: ' . $length);
            }
        }

        // Flush and end all output buffers safely.
        while (ob_get_level() > 0) {
            @ob_end_flush();
        }

        ob_implicit_flush(true);
        @flush();
    }

}
