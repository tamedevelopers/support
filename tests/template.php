<?php 

use Tamedevelopers\Support\Server;
use Tamedevelopers\Support\Translator;


require_once __DIR__ . '/../vendor/autoload.php';


// create php template file with data
$data = [
    'name' => 'Name',
    'username' => 'Username',
    'email' => 'Email',
    'phone' => 'Phone',
    'password' => 'Password',
    'retype_password' => 'Retype password',
    'new_password' => 'New password',
    'forgot_password' => 'Forgot password?',
    'reset_password' => 'Reset password',
    'confirm_password' => 'Confirm password',
    'remember_me' => 'Remember me',
];

/**
 * Custom File Handler
 *
 * @param string $key
 * @param string|null  $default
 * 
 * @return mixed
 */
function __message($key, $default = null){
    return server()->config(
        $key, $default, 'lang'
    );
}

// create a php file that returns an array with the data being passed
server()->createTemplateFile($data, 'lang/file.php');

dd(
    __('file.email', ''),

    __message('file.name'),
    __message('file.email'),
    __message('file.emails', 'Null'),

);

