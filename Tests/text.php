<?php 

use Tamedevelopers\Support\Env;
use Tamedevelopers\Support\Hash;
use Tamedevelopers\Support\Tame;
use Tamedevelopers\Support\Server;
use Tamedevelopers\Support\Slugify;
use Tamedevelopers\Support\Capsule\Forge;

require_once __DIR__ . '/../vendor/autoload.php';


// $Json = '{"name":"Peterson","age":20,"food":["Rice","Garri","Fish","Calories"]}';

// Env::loadOrFail();

// server()->toArray($Json);
// to_object($Json);


// PDF()->create([
//     'content' => "<h1>Title First</h1> <br> Hello There i Love You!",
//     'destination' => base_path('save.pdf'),
//     'output' => 'download'
// ]);

// Tame::unlinkFile('welcome.png', 'upload/avatar/default.png')

// Tame::platformIcon('windows')

// Tame::byteToUnit(2235235235)

// Tame::sizeToBytes('1.5mb')

// Tame::checkHeadersSent()

// Tame::include('normal.php');
// Tame()->include('normal.php');

// bcrypt('testPassword')
// Hash::make('testPassword')
// $2y$10$Frh7yG3.qnGdQ9Hd8OK/y.aBWXFLiFD3IWqUjIWWodUhzIVF3DpT6 --- testPassword

// Hash::check('testPassword', '$2y$10$7a90e2de3f5383819f812u2GwVuprKTsAW7IfeskSkn6/Ky9vSQ.2')

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


/**
 * Custom Language Handler
 *
 * @param  mixed $key
 * @return mixed
 */
function __($key){

    // since the config only takes the filename follow by dot(.) and keyname
    // then we can manually include additional folder-name followed by / to indicate that it's a folder
    // then message.key_name
    // To make this Laravel kind of language, we can add the default value to be returned as the key

    return config("lang/message.{$key}", "message.{$key}", 'Tests');
}


dd(
    // config('log', [], 'storage'),
    __('name'),
    __('confirm_password'),

    
);

echo "hi";