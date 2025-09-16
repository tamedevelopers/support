<?php 

use Tamedevelopers\Support\TextSanitizer;

require_once __DIR__ . '/../vendor/autoload.php';

$sanitizer = new TextSanitizer();

$text = "My number is 0812300089 and another is zero eight one two three four five six seven.
Visit https://x.com google.com or mail me at test@mail.com. +23400000209
I love #coding with @friends. I also have two cats. 11092092-3";

$text2 = "I'm a beautiful zero nine zero and sweet in seven five seven eight available from ";
$text3 = "Reach me on zero eight one double two 555 nine or 0812220000";
$text4 = "Visit https://x.com google.com or mail me at test@mail.com @username.com. +23400";

dd(
    $sanitizer->phoneInt($text),
    $sanitizer->phoneWords($text2),
    $sanitizer->phoneWords($text3),
    $sanitizer->url($text4),
    $sanitizer->email($text4),
    $sanitizer->mention($text4),
    $sanitizer->hastag($text4),
    $sanitizer->sanitize($text),
    $sanitizer->findPhoneWords($text),
);