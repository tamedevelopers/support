<?php 

use Tamedevelopers\Support\ChromePdf\PdfGenerator;

require_once __DIR__ . '/../vendor/autoload.php';


$output = PdfGenerator::create()
    // ->fromHtml('<html><body><p>你好世界</p></body></html>')
    // ->fromFile(base_path('template2.html'))
    // ->fromUrl('https://www.google.com/')
    // ->fromUrl('https://lhkexpress.com/login')
    ->fromUrl('https://lhkexpress.com/blog/Olive-Young-Easter-FREE-shopping-event')
    ->paper('A4') // A4, A3, Letter, Legal, Tabloid
    // ->css('body { font-size: 28px; font-weight: bold; }')
    // ->colorScheme('dark')
    // ->selectElement('.body')
    // ->margins(false)
    ->clickableLinks(false)
    // ->loadRemoteImages(true)
    ->generate();

$output->view();
// $output->download();
