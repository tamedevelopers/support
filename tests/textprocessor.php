<?php 

use Tamedevelopers\Support\TextProcessor;

require_once __DIR__ . '/../vendor/autoload.php';

$proccessor = new TextProcessor();

$text = "My number is 08123456789 and another is zero eight one two three four five six seven.
Visit https://example.com google.com or mail me at test@mail.com. +23409109209
I love #coding with @friends. I also have two cats. 11092092-3";

$text2 = "I'm a beautiful zero nine zero and sweet in seven 
    five seven eight available from ";
    
$text3 = "Reach me on zero eight one double two 555 nine or 0812225559";

dd(
    $proccessor->phoneInt($text),
    $proccessor->phoneWords($text2),
    $proccessor->phoneWords($text3),
    $proccessor->url($text),
    $proccessor->sanitize($text),
    $proccessor->findPhoneWords($text),
);