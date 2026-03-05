<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Traits;


trait MailSMTPTransport{

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
                            'message'   => "Sent", 
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
    
}