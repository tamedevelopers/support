<?php 

use Tamedevelopers\Support\Env;
use Tamedevelopers\Support\Hash;
use Tamedevelopers\Support\Tame;
use Tamedevelopers\Support\Capsule\Forge;

require_once __DIR__ . '/../vendor/autoload.php';


// $Json = '{"name":"Peterson","age":20,"food":["Rice","Garri","Fish","Calories"]}';


// Env::loadOrFail();



Tame::include('normal.php');
// Tame()->include('normal.php');

dd(
    // Tame::byteToUnit(2235235235),
    Tame::unlinkFile('welcome.png', 'upload/avatar/default.png'),
    Tame::setCheckbox('0', true),
    Tame::platformIcon('windows'),
    Tame::sizeToBytes('1.5kb'),
    Tame::checkHeadersSent('1.5kb'),
    Hash::stringHash('1.5kb'),
);

echo "hi";