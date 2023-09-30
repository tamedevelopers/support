<?php 

use Tamedevelopers\Support\Hash;

require_once __DIR__ . '/../vendor/autoload.php';


// bcrypt('testPassword')
// Hash::make('testPassword')
// $2y$10$Frh7yG3.qnGdQ9Hd8OK/y.aBWXFLiFD3IWqUjIWWodUhzIVF3DpT6 --- testPassword

// Hash::check('testPassword', '$2y$10$7a90e2de3f5383819f812u2GwVuprKTsAW7IfeskSkn6/Ky9vSQ.2')


dd(
    Hash::make('testPassword'),

    bcrypt('testPassword'),

    Hash::check('testPassword', '$2y$10$Frh7yG3.qnGdQ9Hd8OK/y.aBWXFLiFD3IWqUjIWWodUhzIVF3DpT6')
);

