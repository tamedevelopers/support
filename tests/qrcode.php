<?php 

use Tamedevelopers\Support\QRCode;

require_once __DIR__ . '/../vendor/autoload.php';


$qr = new QRCode(public_path('qrcode.gift'));

$qr->addText('Coming Home')
    // ->addSocial('snapchat', 'tamedeveloper')
    // ->addWifi('Tame_Guest', 'password***', 'WPA')
    ->addContact([
        'prefix' => 'Dev.',
        'firstName' => 'Tame', 
        'lastName' => 'Developers', 
        'email' => 'tamedevelopers@gmail.com', 
        'title' => 'Software Engineer', 
        'organization' => 'Software'
    ])
    // ->addEvent([
    //     'title'       => 'Strategy Meeting',
    //     'start'       => '2026-10-15 10:00:00',
    //     'end'         => '2026-10-15 11:30:00',
    //     'description' => 'Quarterly planning session',
    //     'location'    => 'Conference Room A',
    // ])
    ->pattern('inverted') //'classic'|'rounded'|'thin'|'smooth'|'circle'|'leaf'|'inverted'|'pillow'|'finder'|'scallop'
    // ->template('facebook')
    // ->shape('rounded', 'green')
    // ->outerShape('inverted', '#043b2d')
    // ->innerShape('classic', '#037d11')
    // ->showIcon(false)
    ->iconPath(base_path('watermark.png'))
    ->save();


echo $qr->toImage();

dd(
    // $qr->toPng(),
    // $qr->toSvg(true),
    $qr->toImage(),
    $qr->getData(),
    $qr->getPath(),
    $qr->getPath('name'),
);