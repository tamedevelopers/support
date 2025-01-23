<?php 

use Tamedevelopers\Support\Mail;

require_once __DIR__ . '/../vendor/autoload.php';


$mailer = Mail::config([
    'host' => 'sandbox.smtp.mailtrap.io',
    'port' => 2525,
    'username' => '19e021f96b1434',
    'password' => 'ac7fc974df4844',
    'encryption' => 'ssl',
    'from_email' => 'noreply@mailtrap.io',
    'from_name' => 'Tame Developers',
]);

$mailer->to('tamedevelopers@gmail.com')
            ->subject('New subject')
            ->body('Hello this is a body text')
            ->attach(
                base_path("thousand_units.png"), 
                'New Name'
            )
            ->send(function($response){
                // dd(
                //     $response
                // );
            });


            
// $mailer->to('tamedevelopers@gmail.com')
//         ->bcc(['fredi.peterson2000@gmail.com'])
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
    'sss'
);