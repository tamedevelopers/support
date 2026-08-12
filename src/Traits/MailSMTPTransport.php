<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Traits;

use Closure;


trait MailSMTPTransport{

    /**
     * Creates a temporary email closure.
     *
     * This method allows you to define a callable that can be used to temporarily
     * modify or handle email-related logic. If no callable is provided, it will
     * use a default behavior.
     *
     * @param Closure|null $closure An optional callable to customize the email handling.
     * @return mixed Returns the result of the callable or the default behavior.
     */
    private function createEmailTempClosure($closure = null)
    {
        $sendEmails = [];

        // We iterate and create a "blueprint" for each email
        foreach($this->recipients['to'] as $email){

            // We return a wrapper function. Nothing inside this block 
            // runs until $fn() is called in your TameCollect loop.
            $sendEmails[] = function() use ($email, $closure) {
                try {

                    // Validate the recipient email
                    if (!Tame()->emailValidator($email, false)) {
                        throw new \Exception("Invalid email address: {$email}", 509); 
                    }

                    // If message body is empty
                    if (empty($this->body)) {
                        throw new \Exception("Email body cannot be empty.", 510);
                    }

                    $fromEmail = $this->from['address'];

                    if(!Tame()->emailValidator($fromEmail, false)){
                        throw new \Exception("Invalid From-Email address: {$fromEmail}", 511);
                    }
                        
                    $this->mailer->setFrom($fromEmail, $this->from['name']);
                    $this->mailer->addAddress($email);
                    
                    // Internal Trait Logic for CC, BCC, and Reply-To
                    $this->addCC();
                    $this->addBCC();
                    $this->addReplyTo();
        
                    // Set email format to HTML
                    $this->mailer->isHTML(true); 
                    $this->mailer->Subject = $this->subject;
                    $this->mailer->Body    = $this->body;
                    
                    $this->addAltBody();

                    // Persistent Connection Check
                    // If the mailer isn't connected (or was closed), reconnect.
                    if (!$this->mailer->getSMTPInstance()->connected()) {
                        $this->mailer->SMTPConnect();
                    }

                    // Connect
                    if (!$this->mailer->send()) {
                        throw new \Exception($this->mailer->ErrorInfo, 500);
                    }
        
                    // get message id
                    $mid = $this->mailer->getLastMessageID();
                    
                    // Delete attachments
                    $this->deleteAttachment();
                    
                    // Return the response to the caller
                    if(is_callable($closure)){
                        call_user_func($closure, (object) [
                            'status'    => 200, 
                            'message'   => "Sent via [{$this->smtpData['transport']}]", 
                            'transport' => $this->smtpData['transport'], 
                            'mid'       => $mid, 
                            'to'        => $email
                        ]);
                    }
                } catch (\Exception $e) {
                    if(is_callable($closure)){
                        call_user_func($closure, (object) [
                            'status'    => $e->getCode(), 
                            'message'   => $e->getMessage(), 
                            'transport' => $this->smtpData['transport'], 
                            'mid'       => null, 
                            'to'        => $email
                        ]);
                    }
                }
            };
        }

        return $sendEmails;
    }
    
}