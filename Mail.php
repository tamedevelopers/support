<?php

namespace Tamedevelopers\Support;

use PHPMailer\PHPMailer\SMTP;
use Tamedevelopers\Support\Str;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
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
    static public function config(?array $options = [])
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
    static public function to(...$emails)
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
        if(func_num_args() >= 1){
            $attachments = ['path' => $attachments[0], 'as' => $attachments[1] ?? null];
        }

        $this->attachments = $this->formatAttachments($attachments);

        foreach ($this->attachments as $path => $name) {
            if(Tame()->exists($path)){
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
     * @param callable $function
     * @return mixed
     */
    public function send(callable $function  = null)
    {
        // configure smtp data
        $this->configureSMTPData($this->options);

        // create default options
        $defaultOption = $this->getDefaultOption($this->options);

        // setup mailer
        $this->setupMailer($defaultOption);

        foreach($this->recipients['to'] as $email){
            // sending mail through auto crons flush
            $this->ob_crons_flush(function() use ($defaultOption, $function, $email) {
                try {
                    // Validate the recipient email
                    $verify = Tame()->emailValidator($email, true, true);
                    if (!$verify) {
                        throw new \Exception("Invalid email address: {$email}", 509); // Custom error code: 509
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

                    // clear added address
                    $this->mailer->clearAddresses();
                    
                    // Close the SMTP session
                    $this->mailer->SMTPClose();
                    
                    // if attachment delete is allowed
                    $this->deleteAttachment();
                    
                    // $this->mail->ErrorInfo
                    if(is_callable($function)){
                        call_user_func($function, (object) [
                            'status'    => 200, 
                            'message'   => 'Sent', 
                            'mid'       => $mid, 
                            'to'        => $email
                        ]);
                    }
                } catch (\Exception $e) {
                    if(is_callable($function)){
                        call_user_func($function, (object) [
                            'status'    => $e->getCode(), 
                            'message'   => $e->getMessage(), 
                            'mid'       => null, 
                            'to'        => $email
                        ]);
                    }
                }
            }, $defaultOption);
        }
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

    /**
     * Flushes output buffer and sends data to client.
     *
     * @param callable|null $callback The function to execute after flushing the buffer.
     * @param array|null $options The options to use during buffer flushing.
     *
     * @return void
     */
    private function ob_crons_flush(callable $function, ?array $options = null)
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
        if(is_callable($function)){
            $function(self::class);
        }
        
        $this->__obFlush($options);

        // Disable implicit flushing
        ob_implicit_flush(false);
    }

    /**
     * Flushes output buffers and sends headers to enable server-side flushing.
     * 
     * @param array $options An array of options to configure the flush behavior.
     * Available options are:
     * - 'flush' (bool) Whether to flush the email sending queue. Defaults to false.
     * - 'debug' (int) Whether to enable debug mode. When debug mode is on, the email sending queue is not flushed. Defaults to false.
     * 
     * @return void
     */
    private function __obFlush(?array $options = [])
    {
        // Flush email sending queue
        if ($options['flush'] && $options['debug'] === 0) {
            if (!headers_sent()) {
                // @header('Surrogate-Control: BigPipe/1.0');
                // @header('X-Accel-Buffering: no');
                // @header("Content-Encoding: none");  
                // @header("Connection: close");
                // @header("Content-Length: " . ob_get_length());

                // $this->obFlush();
            }
        }

        // Flush output buffers if active
        if (ob_get_level() > 0) {
            flush();
            ob_flush();
            ob_end_flush();
        }

        // Clean up output buffers if active
        if (ob_get_level() > 0) {
            ob_clean();
            ob_end_clean();
        }

        // Set implicit flush to true to enable real-time output
        ob_implicit_flush(true);
    }
    
}
