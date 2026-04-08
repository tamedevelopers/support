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

        // We iterate and create a "blueprint" for each email
        foreach($this->recipients['to'] as $email){

            // We return a wrapper function. Nothing inside this block 
            // runs until $fn() is called in your TameCollect loop.
            $sendEmails[] = function() use ($email, $callable) {
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

                    // Important: Clear state first to prevent BCC/CC from leaking to the next recipient
                    $this->mailer->clearAllRecipients();
                    $this->mailer->clearAttachments();
                    $this->mailer->clearCustomHeaders();
                        
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
                    // $this->mailer->SMTPConnect();
                    if (!$this->mailer->send()) {
                        throw new \Exception($this->mailer->ErrorInfo, 500);
                    }
        
                    // get message id
                    $mid = $this->mailer->getLastMessageID();
                    
                    // Post-Send Cleanup
                    // $this->mailer->SMTPClose();
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