<?php 

use Tamedevelopers\Support\ChromePdf\ChromePdf;

require_once __DIR__ . '/../vendor/autoload.php';

$files = [
    '1' => 'template.html',
    '2' => base_path('template2.html'),
    '3' => base_path('template3.html'),
];


// On Windows, Linux, MacOS PHP (xampp, wamp, mamp, etc.), you need to enable the 
// sockets extension in your php.ini file.
// ;extension=sockets

$output = ChromePdf::create()
    ->fromHtml('<html><body><p>你好世界</p></body></html>')
    // ->fromFile($files['1'])
    // ->fromUrl('https://www.google.com')
    ->paper('A4') // A4, letter, Legal, Ledger
    ->colorScheme('dark')
    ->selectElement('.body')
    ->margins(10)
    ->chromiumBinary(base_path('upload/chrome-win/chrome.exe'))
    ->clickableLinks(false)
    ->generate();

// ;

$output->inline();

// $output->view();
// $output->download();
// $output->save('invoice/invoice.pdf');
