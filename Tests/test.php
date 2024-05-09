<?php 

use Tamedevelopers\Support\Env;
use Tamedevelopers\Support\PDF;
use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Tame;
use Tamedevelopers\Support\Slugify;

require_once __DIR__ . '/../vendor/autoload.php';


$Json = '{"name":"Peterson","age":20,"food":["Rice","Garri","Fish","Calories"]}';

// Env::loadOrFail();


// TamePDF()->create([
//     'content' => "<h1>Title First</h1> <br> Hello There i Love You!",
//     'destination' => base_path('save.pdf'),
//     'output' => 'download'
// ]);

// Tame::unlink('welcome.png', 'default.png')

// Tame::platformIcon('windows')

// Tame::byteToUnit(2235235235)

// Tame::sizeToBytes('1.5mb')

// Tame::checkHeadersSent()

// Tame::include('normal.php');
// Tame()->include('normal.php');

// Slugify::slug('Hottest Product 2023, For Health Benefits');

// Server::createTemplateFile([
//     'name' => 'Name',
//     'username' => 'Username',
//     'email' => 'Email',
//     'phone' => 'Phone',
//     'password' => 'Password',
//     'retype_password' => 'Retype password',
//     'new_password' => 'New password',
//     'forgot_password' => 'Forgot password?',
//     'reset_password' => 'Reset password',
//     'confirm_password' => 'Confirm password',
//     'remember_me' => 'Remember me',
// ], 'Tests/en.php');


$svg = Tame::platformIcon('windows');

include $svg;


// Define an array to sort
$data = [4, 2, 7, 1, 5];

Str::sortArray($data, 'krsort');

dd(
    Tame::platformIcon('windows'), 

    to_object($Json),

    to_array($Json),

    $data,

    // server()->toArray($Json),
);

