<?php

namespace Tamedevelopers\Support;

use Tamedevelopers\Support\Str;
use PHPMailer\PHPMailer\PHPMailer;
use Tamedevelopers\Support\Capsule\File;
use Tamedevelopers\Support\Capsule\Manager;
use Tamedevelopers\Support\Traits\MailTrait;
        
class Mail{

    use MailTrait;

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
     * - host | port | username | password | encryption
     * - from_email | from_name | 
     * - base\Base directory
     * 
     * @return $this
     */
    public static function config(?array $options = [])
    {
        if(!defined(self::$constantName)){
            define(self::$constantName, $options);
        }

        return new static([], []);
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

        return new static(
            $emails,
            self::getConfig()
        );
    }

    /**
     * Add CC recipients.
     *
     * @param string|array $emails
     * @return $this
     */
    public function cc($emails)
    {
        $this->recipients['cc'] = $this->convert($emails, 'email');

        return $this;
    }

    /**
     * Add BCC recipients.
     *
     * @param string|array $emails
     * @return $this
     */
    public function bcc($emails)
    {
        $this->recipients['bcc'] = $this->convert($emails, 'email');

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
     * Set the email body.
     *
     * @param string $driver
     * - [optional] Default is smtp
     * 
     * @return $this
     */
    public function driver($driver = 'smtp')
    {
        $driver = Str::lower($driver);

        $this->driver = match ($driver) {
            'mail' => 'isMail',
            'smtp' => 'isSMTP',
            default => 'isSMTP'
        };

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

        // setup mailer
        $this->setupMailer($defaultOption);

        // create email closures
        $sendEmails = $this->createEmailTempClosure($callable);

        TameCollect($sendEmails)
            ->each(function($fn) use ($defaultOption) {
                // If flushBuffering is enabled, release response and send in background
                if ($this->flushBuffering) {
                    $this->ob_crons_flush($fn, $defaultOption);
                } else{
                    $fn();
                }
            });
    }
    
    /**
     * obFlush
     *
     * @return void
     */
    public function obFlush() 
    {
        @header("Connection: close");
        @header("Content-length: " . ob_get_length());
        @ob_end_flush();
        @flush();
        @session_write_close();
    }

}
