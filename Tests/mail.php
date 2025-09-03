<?php 

use Tamedevelopers\Support\Mail;
use Tamedevelopers\Support\Capsule\File;

require_once __DIR__ . '/../vendor/autoload.php';


// config mail manually here or .env file
$mailer = Mail::config([
    // 'host' => 'sandbox.smtp.mailtrap.io',
    // 'port' => 587,
    // 'username' => '',
    // 'password' => '',
    // 'encryption' => 'ssl',
    // 'from_email' => 'noreply@mailtrap.io',
    // 'from_name' => 'Tame Developers',
]);


$mailer->to('tamedevelopers@gmail.com')
        ->subject('New subject')
        ->body('Hello this is a body text')
        ->attach(
            'New Units File',
            base_path("thousand_units.png"), 
        );
        // ->send(function($response){
        //     // $response
        // });


            
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