<?php 

use Tamedevelopers\Support\Capsule\File;
use Tamedevelopers\Support\ChromePdf\PdfGenerator;

require_once __DIR__ . '/../vendor/autoload.php';



$files = [
    '1' => base_path('template.html'),
    '2' => base_path('template2.html'),
];

$output = PdfGenerator::create()
    // ->fromHtml('<html><body><p>你好世界</p></body></html>')
    // ->fromFile($files['1'])
    // ->fromFile($files['2'])
    // ->fromUrl('https://www.google.com/')
    // ->fromUrl('https://www.noahimports.com/')
    ->fromUrl('https://lhkexpress.com/login')
    // ->fromUrl('https://lhkexpress.com/blog/Olive-Young-Easter-FREE-shopping-event')
    ->paper('A4') // 
    // ->css('body { font-size: 28px; font-weight: bold; }')
    // ->colorScheme('dark')
    // ->selectElement('.body')
    // ->margins(false)
    ->clickableLinks(false)
    ->generate();

$output->view();
// $output->download();
