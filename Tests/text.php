<?php 

use Tamedevelopers\Support\Env;
use Tamedevelopers\Support\Hash;
use Tamedevelopers\Support\Tame;
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

// bcrypt('testPassword')


// Tame::include('normal.php');
// Tame()->include('normal.php');

// Hash::hash('testPassword')
// $2y$10$Frh7yG3.qnGdQ9Hd8OK/y.aBWXFLiFD3IWqUjIWWodUhzIVF3DpT6 --- testPassword

// Hash::check('testPassword', '$2y$10$7a90e2de3f5383819f812u2GwVuprKTsAW7IfeskSkn6/Ky9vSQ.2')


Slugify::slug('Hi');

dd(
    bcrypt('testPassword'),
    Hash::check('testPassword', '$2y$10$RlIzAs741UcXSnYpgmjTducr8vHPH6BQwyvUlHFSkKCOKhzxy78uK'),
);

echo "hi";