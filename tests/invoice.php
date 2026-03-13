<?php 

use Tamedevelopers\Support\ReceiptGenerator;

require_once __DIR__ . '/../vendor/autoload.php';


$receipt = new ReceiptGenerator();

// Set basic data
$receipt->setData([
    'amount' => 3553333.75,
    'customer_name' => 'Jane Smith',
    'customer_email' => 'jane@example.com',
    'description' => 'Web Development Services',
    'tax_amount' => 508.08,
    'currency' => 'USD',
    'company_name' => 'Tech Solutions Inc.',
    'company_address' => 'Example address - Lagos, Nigeria.',
    'company_phone' => '+234 9XXXX XXXX',
    'tax_amount' => 0.00,
    'total_amount' => 0.00,
    'footer' => 'Thank you for using our services!',
    'status' => 'PAID'
]);

// Choose design and format
$receipt->setPaperSize('a4') //55mm|80mm|a4|a5|letter|legal|square
        ->setDesign('corporate') // ['modern', 'classic', 'minimal', 'corporate', 'invoice']
        ->generate(false)
        ->badge(true)
        // ->setFontFiles()
        ->setOutputFormat('png');


// Download directly to browser
// $receipt->outputToBrowser('payment_receipt');
$path = $receipt->saveToFile(base_path('upload/receipt_001.jpg'));

dd(
    $path,
    $receipt
);
exit;