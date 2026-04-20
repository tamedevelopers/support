<?php

declare(strict_types=1);

use Tamedevelopers\Support\ChromePdf\ChromePdf;

require_once __DIR__ . '/../vendor/autoload.php';

$files = [
    '1' => 'upload/template.html',
    '2' => base_path('upload/template2.html'),
    '3' => base_path('upload/template3.html'),
];

$output = ChromePdf::create()
    // ->fromUrl('https://lhkexpress.com')
    // ->fromFile('upload/template.html')
    ->fromHtml('<html><body><h1>Hello World</h1></body></html>')
    ->margin(20)
    ->createFromElement('.body')
    // ->textWatermark('CONFIDENTIAL')
    // ->clickableLinks(false)
    // ->encrypt(
    //     userPassword: 'user', 
    //     ownerPassword: 'owner', 
    //     blockedPermissions: ['copy', 'print'],
    // )
    // ->chromiumBinary('upload/chrome-win/chrome.exe')
    ->generate();

$output->inline();

// $output->view();
// $output->download();
// $output->save('invoice/invoice.pdf');
