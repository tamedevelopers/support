<?php 

use Tamedevelopers\Support\Capsule\File;
use Tamedevelopers\Support\ChromePdf\PdfGenerator;
use HeadlessChromium\BrowserFactory;


require_once __DIR__ . '/../vendor/autoload.php';


$factory = new BrowserFactory();


// 1. Configure the browser to not be in 'headless' mode if you want to see it,
// // though --headless works too with the settings below.
// $browser = $factory->createBrowser([
//     'windowSize' => [1920, 1080], // 2. Set desktop resolution
//     'userAgent'  => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.212 Safari/537.36', // 3. Desktop User Agent
// ]);

// try {
//     $page = $browser->createPage();

//     // 4. Force desktop emulation settings
//     $page->setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.212 Safari/537.36');
//     $page->setViewport(1920, 1080); // Set viewport to desktop size

//     $page->navigate('https://www.lhkexpress.com')->waitForNavigation();
    
//     // Screenshot to verify desktop view
//     $page->screenshot()->saveToFile('desktop-view.png');

// } finally {
//     $browser->close();
// }

// exit;



$files = [
    '1' => base_path('template.html'),
    '2' => base_path('template2.html'),
];

$output = PdfGenerator::create()
    // ->fromHtml('<html><body><p>你好世界</p></body></html>')
    // ->fromFile($files['1'])
    // ->fromFile($files['2'])
    ->fromUrl('https://www.google.com/')
    // ->fromUrl('https://www.noahimports.com/')
    // ->fromUrl('https://lhkexpress.com/login')
    // ->fromUrl('https://lhkexpress.com/blog/Olive-Young-Easter-FREE-shopping-event')
    ->paper('A4') // A4, letter, Legal, Ledger
    // ->css('body { font-size: 28px; font-weight: bold; }')
    // ->colorScheme('dark')
    // ->selectElement('.body')
    ->margins(20)
    ->clickableLinks(false)
    ->generate();

$output->view();
// $output->download();
