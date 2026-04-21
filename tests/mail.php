<?php 

use Tamedevelopers\Support\Mail;
use Tamedevelopers\Support\Capsule\File;

require_once __DIR__ . '/../vendor/autoload.php';


// config mail manually here or .env file
// now supports Zeptomail API (which uses HTTP to send mail instead of SMTP)
// every method call have to come after you've called the ->to() method

$mailer = Mail::config([
    // 'transport' => 'smtp', //zeptomail|sendgrid|mailgun|mailjet|postmark|ses|mailchimp|socketlabs|elastic|brevo
    // 'host' => 'sandbox.smtp.mailtrap.io',
    // 'port' => 587,
    // 'username' => '',
    // 'password' => '',
    // 'encryption' => 'ssl',
    // 'from' => ['address' => 'noreply@mailtrap.io', 'name' => 'Name'],
    // 'url' => 'https://api.zeptomail.com/v1.1/email',
    // 'token' => 'api_token_or_key',
    // 'secret' => 'api_secret',
    // 'region' => 'api_region', //for amazon-ses
]);

// env configuration
// MAIL_MAILER=smtp
// MAIL_HOST=smtp.zeptomail.com
// MAIL_PORT=587 
// MAIL_USERNAME=noreply@example.com
// MAIL_PASSWORD=""
// MAIL_ENCRYPTION=tls
// MAIL_FROM_ADDRESS="noreply@example.com"
// MAIL_FROM_NAME="Tame Developers"
// MAIL_URL=""
// MAIL_TOKEN=""
// MAIL_SECRET=""
// MAIL_REGION=""


$mailer->obFlush();

$mailer
    ->to('tamedevelopers@gmail.com')
    ->subject('New subject')
    ->body('Hello this is a body text')
    ->altBody('fff')
    ->attach(
        'New Units File',
        base_path("thousand_units.png"), 
    )
    ->flush(true)
    ->send(function($response){
        // $response
        dump(
            $response
        );
    });

            
// $mailer->to('tamedevelopers@gmail.com')
//         ->bcc(['example-email@gmail.com'])
//         ->replyTo('tamedevelopers@gmail.com', 'Jeffrey Way')
//         ->attach(base_path("thousand_units.png"), 'New Name')
//         ->delete(false)
//         ->subject('New subject')
//         ->body('Hello this is a body text')
//         ->flush(false)
//         ->send(function($response){
//             dd(
//                 $response
//             );
//         });


dump(
    $mailer,
    'last dump()'
);
