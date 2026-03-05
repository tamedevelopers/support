<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Traits;

use Tamedevelopers\Support\Capsule\File;
use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Tame;


trait MailApiTransport{

    /**
     * Checking if API Call
     */
    private function isAPI(): bool
    {
        return $this->driver === 'isAPI';
    }
    
    /**
     * Checking if Call is AWS
     */
    private function isAWS(): bool
    {
        return $this->provider === 'aws';
    }
    
    /**
     * Checking if Call is Postmark
     */
    private function isPostmark(): bool
    {
        return $this->provider === 'postmark';
    }
    
    /**
     * Checking if Call is Mailchimp
     */
    private function isMailchimp(): bool
    {
        return $this->provider === 'mailchimp';
    }
    
    /**
     * Checking if Call is Elastic
     */
    private function isElastic(): bool
    {
        return $this->provider === 'elastic';
    }

    /**
     * Build API Base Payload
     * 
     * @param string $email
     * @return array
     */
    private function apiPayloadBuilder($email)
    {
        $from = [
            'address' => $this->smtpData['from_email'],
            'name'    => $this->smtpData['from_name']
        ];

        return match($this->provider) {
            'sendgrid' => [
                'personalizations' => [[ 'to' => [[ 'email' => $email ]] ]],
                'from' => $from,
                'subject' => $this->subject,
                'content' => [[ 'type' => 'text/html', 'value' => $this->body ]]
            ],
            'mailgun' => [
                'from'    => "{$from['name']} <{$from['address']}>",
                'to'      => $email,
                'subject' => $this->subject,
                'html'    => $this->body
            ],
            'mailjet' => [
                'Messages' => [[
                    'From' => $from,
                    'To'   => [['Email' => $email]],
                    'Subject' => $this->subject,
                    'HTMLPart' => $this->body
                ]]
            ],
            'postmark' => [
                'From'        => $from['address'],
                'To'          => $email,
                'Subject'     => $this->subject,
                'HtmlBody'    => $this->body,
                'TextBody'    => $this->altbody ?? $this->body,
                'Tag'         => $this->smtpData['tag'] ?? null,
                'TrackOpens'  => $this->smtpData['track_opens'] ?? true,
                'TrackLinks'  => $this->smtpData['track_links'] ?? 'None',
                'MessageStream'=> $this->smtpData['message_stream'] ?? 'outbound',
                'Attachments' => [],           // Will be populated via attachPostfieldMultiparts()
            ],
            'aws' => [
                'Source' => $from['address'],
                'Destination' => ['ToAddresses' => [$email]],
                'Message' => [
                    'Subject' => ['Data' => $this->subject, 'Charset' => 'UTF-8'],
                    'Body'    => ['Html' => ['Data' => $this->body, 'Charset' => 'UTF-8']]
                ]
            ],
            'mailchimp' => [
                'key' => $this->smtpData['api_token'],
                'message' => [
                    'from_email' => $from['address'],
                    'from_name'  => $from['name'],
                    'subject'    => $this->subject,
                    'html'       => $this->body,
                    'to' => [
                        [
                            'email' => $email,
                            'type'  => 'to'
                        ]
                    ]
                ]
            ],
            'socketlabs' => [
                'ServerId' => (int) $this->smtpData['api_secret'],
                'Messages' => [
                    [
                        'From' => [
                            'EmailAddress' => $from['address'],
                            'FriendlyName' => $from['name']
                        ],
                        'To' => [
                            [
                                'EmailAddress' => $email
                            ]
                        ],
                        'Subject' => $this->subject,
                        'HtmlBody' => $this->body
                    ]
                ]
            ],
            'elastic' => [
                "Recipients" => [
                    "To" => [$email]
                ],
                "Content" => [
                    "From" => "{$from['name']} <{$from['address']}>",
                    "Subject" => $this->subject,
                    "Body" => [
                        [
                            "ContentType" => "HTML",
                            "Charset" => "utf-8",
                            "Content" => $this->body
                        ]
                    ]
                ]
            ],
            default => [ // zeptomail
                'from'    => $from,
                'to'      => [['email_address' => ['address' => $email]]],
                'subject' => $this->subject,
                'htmlbody'=> $this->body
            ]
        };
    }

    /**
     * Get Api Default Header
     */
    private function getDefaultHeaders(): array
    {
        $token      = $this->smtpData['api_token'] ?? '';
        $secret     = $this->smtpData['api_secret'] ?? '';
        $headers    = [];

        switch ($this->provider) {
            case 'mailchimp': // Mandrill Transactional
            case 'sendgrid':
                $headers = [
                    "Authorization: Bearer {$token}",
                    "Content-Type: application/json"
                ];
                break;
            case 'mailgun':
                $headers = [
                    "Authorization: Basic " . base64_encode("api:{$token}"),
                    "Content-Type: application/json"
                ];
                break;
            case 'mailjet':
                if (empty($secret)) {
                    throw new \Exception("Mailjet requires API_SECRET.", 512);
                }
                $headers = [
                    "Authorization: Basic " . base64_encode("{$token}:{$secret}"),
                    "Content-Type: application/json"
                ];
                break;
            case 'postmark':
                $headers = [
                    "X-Postmark-Server-Token: {$token}",
                    "Content-Type: application/json"
                ];
                break;
            case 'socketlabs': 
                $headers =  [
                    "X-Server-Id: {$secret}",
                    "X-Api-Key: {$token}",
                    "Content-Type: application/json"
                ];
                break;
            case 'elastic': 
                $headers =  [
                    "X-ElasticEmail-ApiKey: {$token}",
                    "Content-Type: application/json"
                ];
                break;
            default: // zeptomail
                $headers = [
                    "authorization: {$token}",
                    "cache-control: no-cache",
                    "Content-Type: application/json"
                ];
        }

        return $headers;
    }

    /**
     * Get Api Header (Amazon SES) Amazon Simple Email Service
     * @return array
     */
    private function getApiHeaders()
    {
        if(!$this->isAWS()){
            return $this->getDefaultHeaders();
        }

        // Ensure AWS SDK exists
        if (!class_exists('\Aws\SesV2\SesV2Client')) {
            throw new \Exception(
                "AWS SDK not installed. Run: composer require aws/aws-sdk-php",
                520
            );
        }

        // SDK handles signing internally
        return [];
    }

    /**
     * Build Postfields multiparts
     * 
     * @param array $payload
     * @return array
     */
    private function buildPostfieldMultiparts($payload)
    {
        $postFields = [];

        switch($this->provider){
            case 'sendgrid':
                // Ensure 'from' is an object with 'email' and optional 'name'
                $from = $payload['from'] ?? [];
                if (!isset($from['email'])) {
                    $from = [
                        'email' => $this->smtpData['from_email'] ?? '',
                        'name'  => $this->smtpData['from_name'] ?? ''
                    ];
                }

                $postFields = [
                    'personalizations' => $payload['personalizations'] ?? [],
                    'from'             => $from,
                    'subject'          => $payload['subject'] ?? '',
                    'content'          => $payload['content'] ?? []
                ];
                break;
            case 'mailjet':
                $postFields = $payload['Messages'] ?? [];
                break;
            case 'mailgun':
            case 'postmark':
            case 'mailchimp':
            case 'socketlabs':
            case 'elastic':
                $postFields = $payload;
                break;
            default: 
                $postFields = [
                    'from'     => $payload['from'] ?? [],
                    'to'       => $payload['to'] ?? [],
                    'subject'  => $payload['subject'] ?? '',
                    'htmlbody' => $payload['htmlbody'] ?? '',
                    'textbody' => $payload['textbody'] ?? ''
                ];
                break;
        }

        return $postFields;
    }

    /**
     * Attach Postfields multiparts
     * 
     * @param array $postFields
     * @param array $payload
     */
    private function attachPostfieldMultiparts(&$postFields, $payload): void
    {
        // Add CC, BCC, Reply-To if provider supports them
        if (!empty($payload['cc'])) {
            $postFields['cc'] = $payload['cc'];
        }

        if (!empty($payload['bcc'])) {
            $postFields['bcc'] = $payload['bcc'];
        }

        if (!empty($payload['reply_to'])) {
            $postFields['reply_to'] = $payload['reply_to'];
        }

        // Add attachments, normalized for all providers
        if(!empty($this->attachments)){
            $attachments = [];
            foreach ($this->attachments as $path => $name) {
                if (File::exists($path)) {
                    $attachments[] = match($this->provider){
                        'sendgrid', 'mailjet' => [
                            'content' => Tame::imageToBase64($path, false, true),
                            'filename' => $name,
                            'type' => mime_content_type($path)
                        ],
                        'postmark', 'socketlabs' => [
                            'Name' => $name,
                            'Content' => Tame::imageToBase64($path, false, true),
                            'ContentType' => mime_content_type($path)
                        ],
                        'mailchimp' => [
                            'type'    => mime_content_type($path),
                            'name'    => $name,
                            'content' => Tame::imageToBase64($path, false, true)
                        ],
                        'elastic' => [
                            'BinaryContent' => Tame::imageToBase64($path, false, true),
                            "ContentType" => mime_content_type($path),
                            'name' => $name,
                        ],
                        default => [
                            'name' => $name,
                            'mime_type' => mime_content_type($path),
                            'content' => Tame::imageToBase64($path, false, true)
                        ]
                    };
                }
            }

            if(!empty($attachments)){
                $attachmentName = match($this->provider){
                    'sendgrid', 'mailjet', 'postmark', 'socketlabs' => 'Attachments',
                    default => 'attachments'
                };

                if($this->isMailchimp()){
                    $postFields['message']['attachments'] = $attachments;
                } elseif($this->isElastic()){
                    $postFields['Content']['Attachments'] = $attachments;
                }  else{
                    $postFields[$attachmentName] = $attachments;
                }
            }
        }
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
    private function createApiEmailTempClosure($callable = null)
    {
        $sendEmails = [];

        foreach ($this->recipients['to'] as $email) {
            $sendEmails[] = function() use ($email, $callable) {
                try {

                    $apiUrl = $this->smtpData['api_url'];
                    $apiToken = $this->smtpData['api_token'];

                    // setup error or missing
                    if(empty($apiUrl) || empty($apiToken)){
                        throw new \Exception(
                            sprintf("Missing Setup Data: MAIL_API_URL(%s) or MAIL_API_TOKEN(%s)", $apiUrl, $apiToken), 
                            508
                        );
                    }

                    // Validate the recipient email
                    if (!Tame()->emailValidator($email, true)) {
                        throw new \Exception("Invalid email address: {$email}", 509);
                    }
                    
                    // If message body is empty
                    if (empty($this->body)) {
                        throw new \Exception("Email body cannot be empty.", 510);
                    }

                    $fromEmail = $this->smtpData['from_email'];
                    
                    if (!Tame()->emailValidator($fromEmail, true)) {
                        throw new \Exception("Invalid From-Email address: {$fromEmail}", 511);
                    }

                    // Build Base Payload
                    $payload = $this->apiPayloadBuilder($email);

                    // If support alternative message
                    $this->addAltBody($payload, true);

                    // add cc
                    $this->addCC($payload, true);

                    // add bcc
                    $this->addBCC($payload, true);

                    // add reply to
                    $this->addReplyTo($payload, true);
                    
                    // Convert to Multipart Fields
                    $postFields = $this->buildPostfieldMultiparts($payload);

                    $this->attachPostfieldMultiparts($postFields, $payload);

                    // AWS SES via SDK or API
                    if ($this->isAWS()) {
                        $this->sendViaAWS($callable, $email);
                    } else{
                        $this->sendViaCurl($apiUrl, $postFields, $callable, $email);
                    }
                } catch (\Exception $e) {
                    if(is_callable($callable)){
                        call_user_func($callable, (object)[
                            'status' => $e->getCode(),
                            'message' => $e->getMessage(),
                            'mid' => null,
                            'to' => $email
                        ]);
                    }
                }
            };
        }

        return $sendEmails;
    }
    
    /**
     * sendViaAWS
     *
     * @param  mixed $callable
     * @param  mixed $email
     * @return void
     */
    private function sendViaAWS($callable, $email)
    {
        $client = new \Aws\SesV2\SesV2Client([
            'version'     => 'latest',
            'region'      => $this->smtpData['api_region'],
            'credentials' => [
                'key'    => $this->smtpData['api_token'],
                'secret' => $this->smtpData['api_secret'],
            ],
        ]);

        $replyTo    = $this->recipients['reply_to'];
        $address    = $replyTo[0] ?? null;
        $name       = $replyTo[1] ?? '';

        // Build Destination
        $destination = [
            'ToAddresses' => [$email],
        ];

        if (!empty($this->recipients['cc'])) {
            $destination['CcAddresses'] = $this->recipients['cc'];
        }

        if (!empty($this->recipients['bcc'])) {
            $destination['BccAddresses'] = $this->recipients['bcc'];
        }

        // If attachments exist → RAW EMAIL
        if (!empty($this->attachments)) {
            $mime = $this->mailer;
            $mime->setFrom($this->smtpData['from_email'], $this->smtpData['from_name']);
            $mime->addAddress($email);

            if(!empty($this->recipients['cc'])){
                foreach ($this->recipients['cc'] ?? [] as $cc) {
                    $mime->addCC($cc);
                }
            }

            if(!empty($this->recipients['bcc'])){
                foreach ($this->recipients['bcc'] ?? [] as $bcc) {
                    $mime->addBCC($bcc);
                }
            }

            if (!empty($address)) {
                $mime->addReplyTo($address, $name);
            }

            $mime->isHTML(true);
            $mime->Subject = $this->subject;
            $mime->Body    = $this->body;

            if($this->altbody){
                $mime->AltBody = $this->altbody;
            }

            foreach ($this->attachments as $path => $name) {
                if (File::exists($path)) {
                    $mime->addAttachment($path, $name);
                }
            }

            $mime->preSend();

            $result = $client->sendEmail([
                'FromEmailAddress' => $this->smtpData['from_email'],
                'Destination'      => $destination,
                'Content' => [
                    'Raw' => [
                        'Data' => $mime->getSentMIMEMessage()
                    ]
                ]
            ]);

        } else {

            // Simple Email
            $bodyData = [
                'Html' => [
                    'Data'    => $this->body,
                    'Charset' => 'UTF-8',
                ],
            ];

            if($this->altbody){
                $bodyData['Text'] = [
                    'Data'    => $this->altbody,
                    'Charset' => 'UTF-8',
                ];
            }

            // Include Reply-To if available
            $replyToAddresses = [];
            if (!empty($address)) {
                $replyToAddresses[] = $address;
            }

            $result = $client->sendEmail([
                'FromEmailAddress' => $this->smtpData['from_email'],
                'Destination'      => $destination,
                'ReplyToAddresses' => $replyToAddresses,
                'Content' => [
                    'Simple' => [
                        'Subject' => [
                            'Data'    => $this->subject,
                            'Charset' => 'UTF-8',
                        ],
                        'Body' => $bodyData,
                    ],
                ],
            ]);
        }

        $this->deleteAttachment();

        if (is_callable($callable)) {
            call_user_func($callable, (object)[
                'status' => 200,
                'message' => 'Sent via AWS SES SDK',
                'mid' => $result['MessageId'] ?? null,
                'to' => $email,
                'response' => $result
            ]);
        }
    }
    
    /**
     * Sending Email Via Curl-HTTP Request
     *
     * @param  mixed $apiUrl
     * @param  mixed $postFields
     * @param  mixed $callable
     * @param  mixed $email
     * @return void
     */
    private function sendViaCurl($apiUrl, $postFields, $callable, $email)
    {
        // using curl to send request
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_HTTPHEADER => $this->getApiHeaders(),
        ));

        if ($this->isPostmark()) {

            // If Cc or Bcc exists, leave them as formatted strings
            foreach (['Cc', 'Bcc'] as $field) {
                if (!empty($postFields[$field])) {
                    // Make sure it's a string, just in case it's an array
                    if (is_array($postFields[$field])) {
                        $postFields[$field] = implode(', ', array_map(function($item) {
                            if (is_array($item)) {
                                $name = !empty($item['Name']) ? "\"{$item['Name']}\" " : '';
                                return $name . "<{$item['Email']}>";
                            }
                            return (string)$item;
                        }, $postFields[$field]));
                    }
                }
            }

            // Remove empty fields
            $payload = array_filter($postFields, function($value, $key) {
                return !is_array($value) ? !empty($value) : true; // Keep arrays for further processing
            }, ARRAY_FILTER_USE_BOTH);

            // Encode to JSON without escaping slashes and unicode characters
            $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonPayload);
            curl_setopt($curl, CURLOPT_HTTPHEADER, [
                "X-Postmark-Server-Token: {$this->smtpData['api_token']}",
                'Content-Type: application/json', 
            ]);
        } else {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postFields));
        }

        $response   = curl_exec($curl);
        $httpCode   = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err        = curl_error($curl);

        // if attachment delete is allowed
        $this->deleteAttachment();

        if($err){
            unset($curl);
            throw new \Exception("cURL error: {$err}", 500);
        }

        $decodedResponse = json_decode($response, true) ?: $response;

        if ($httpCode < 200 || $httpCode >= 300) {
            $responseText = (is_array($decodedResponse) ? json_encode($decodedResponse) : $decodedResponse);
            throw new \Exception(
                sprintf("%s - API request failed with HTTP status %d: %s", $this->provider, $httpCode, $responseText),
                $httpCode
            );
        }

        if(is_callable($callable)){
            call_user_func($callable, (object)[
                'status' => 200,
                'message' => "Sent via API - {$this->provider}",
                'mid' => null,
                'to' => $email,
                'response' => $response
            ]);
        }
    }

}