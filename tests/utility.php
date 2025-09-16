<?php 

use Tamedevelopers\Support\Utility;

require_once __DIR__ . '/../vendor/autoload.php';


// --------------------------
// Example usage
// --------------------------
$text = "
The Christ the Redeemer Sanctuary, located in the heart of Rio de Janeiro, is the world’s first open-air sanctuary, 
offering a sacred space welcoming people of all cultures, beliefs, and backgrounds. 
More than just a monument, it is a symbol of faith, hope, and hospitality, inviting millions of visitors each year 
for moments of reflection, spirituality, and connection with nature.

Built atop Corcovado Mountain at 710 meters above sea level, Christ the Redeemer is one of the Seven Wonders of the Modern World. 
Inaugurated in 1931, the 38-meter-tall monument was designed by engineer Heitor da Silva Costa and sculpted 
by Paul Landowski, blending art and spirituality harmoniously with the natural landscape.

Elevated to the status of Archdiocesan Sanctuary in 2006, Christ the Redeemer plays a vital role not only as 
a religious landmark but also as a promoter of social and environmental initiatives that reflect Gospel values 
and the United Nations’ Sustainable Development Goals.";


$util = Utility::text($text);
$email = "maxwell+freeman@xd.org";
$email2 = "maxwell.freeman@gmail.com";

// echo($util)  . PHP_EOL;

dump(
    Utility::maskEmail($email),
    Utility::normalizeEmail($email),
    Utility::equalsEmail($email, $email, true),
    // Utility::validateEmail($email, true, true),
    
    // $util->getText(),
    Utility::text('This is a new text')->readingTime(),
    $util->readingTime(),
    $util->wordCount(),
    $util->charCount(),
    $util->sentenceCount(),
    Utility::text("A man, a plan, a canal: Panama")->isPalindrome(),
    // $util->reverse(),
);