<?php 

use Tamedevelopers\Support\PDF;
use Tamedevelopers\Support\TextProcessor;

require_once __DIR__ . '/../vendor/autoload.php';



$procewssor = new TextProcessor();


$text = "My number is 08123456789 and another is zero eight one two three four five six seven.
Visit https://example.com or mail me at test@mail.com. +23409109209
I love #coding with @friends. I also have two cats. 11092092-3";

$text2 = "I'm a beautiful zero nine zero and sweet in seven five seven eight available from nine two two four ";

dd(
    $procewssor->phoneInt($text),
    $procewssor->phoneWords($text2),
    $procewssor->url($text),
);