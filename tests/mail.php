<?php 

use Tamedevelopers\Support\Mail;
use Tamedevelopers\Support\Capsule\File;

require_once __DIR__ . '/../vendor/autoload.php';


// config mail manually here or .env file
// now supports Zeptomail API (which uses HTTP to send mail instead of SMTP)
// every method call have to come after you've called the ->to() method

$mailer = Mail::config([
    // 'driver' => 'api', //api|mail|smtp
    // 'provider' => 'api', //zeptomail|sendgrid|mailgun|mailjet|postmark|aws|mailchimp|socketlabs|elastic
    // 'host' => 'sandbox.smtp.mailtrap.io',
    // 'port' => 587,
    // 'username' => '',
    // 'password' => '',
    // 'encryption' => 'ssl',
    // 'from_email' => 'noreply@mailtrap.io',
    // 'from_name' => 'Tame Developers',
    // 'api_url' => 'https://api.zeptomail.com/v1.1/email',
    // 'api_token' => 'api_token_or_key',
    // 'api_secret' => 'api_secret',
    // 'api_region' => 'api_region', //for amazon-ses
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
// MAIL_DRIVER=""
// MAIL_PROVIDER=""
// MAIL_API_URL=""
// MAIL_API_TOKEN=""
// MAIL_API_SECRET=""
// MAIL_API_REGION=""



$mailer
        // ->to('notification@uphlb.com')
        ->to('tamegurus@gmail.com')
        ->driver('api')
        ->provider('elastic')
        // ->bcc('notification@uphlb.com', 'notification@uphlb.com')
        // ->cc(['tamegurus@gmail.com', 'notification@uphlb.com'])
        ->reply('tamedevelopers@gmail.com', 'Tame Developers')
        ->subject('New subject')
        ->body('Hello this is a body text')
        ->altBody('fff')
        ->attach(
            'New Units File',
            base_path("thousand_units.png"), 
        )
        ->send(function($response){
            // $response
            dd(
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



dd(
    $mailer,
    'sss'
);