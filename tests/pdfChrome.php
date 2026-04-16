<?php

declare(strict_types=1);

/**
 * Chrome PDF smoke / demo script.
 *
 * Optional demos at the bottom need Composer dev deps: setasign/fpdi, tecnickcom/tcpdf.
 *
 */

use Tamedevelopers\Support\ChromePdf\ChromePdf;
use Tamedevelopers\Support\ChromePdf\Exception\ConversionFailedException;
use HeadlessChromium\BrowserFactory;

require_once __DIR__ . '/../vendor/autoload.php';

/** Set true to run merge + reprocessPdf demos after the main PDF (writes under upload/). */
const PDF_CHROME_RUN_PIPELINE_DEMOS = false;


$files = [
    '1' => 'upload/template.html',
    '2' => base_path('upload/template2.html'),
    '3' => base_path('upload/template3.html'),
];

// On Windows, Linux, MacOS PHP (xampp, wamp, mamp, etc.), you need to enable the
// sockets extension in your php.ini file.
// ;extension=sockets

$output = ChromePdf::create()
    // ->fromHtml('<html><body><p>你好世界</p></body></html>')
    ->fromFile($files['1'])
    // ->fromUrl('https://www.google.com')
    ->paper('A4') // A4, letter, Legal, Ledger
    ->colorScheme('dark')
    ->printFromElement('.body')
    ->hideElements('.row-content::nth-child(1)', '.footer')
    // ->margin(20)
    // Native Chromium header/footer (HTML templates; classes: date, title, url, pageNumber, totalPages)
    ->headerHtml()
    ->footerHtml()
    // ->landscape()
    // ->headerFooterColor('white', 'white')
    ->headerFooterBackground('transparent')
    ->textWatermark('CONFIDENTIAL')
    // ->imageWatermark('upload/zimage/header.png')
    // Document metadata (incremental Info update when fpdi present; keeps Chromium link annotations)
    // ->documentMetadata(title: 'Invoice', author: 'Acme', subject: 'Q1', keywords: 'invoice,demo')
    // Passwords + permission **blocks** (TCPDF: list permissions to disallow—e.g. disallow copy but allow print)
    // ->encrypt(
    //     userPassword: 'user', 
    //     ownerPassword: 'owner', 
    //     blockedPermissions: ['copy', 'print'],
    // )
    // PDF/A (1 or 3); cannot combine with encrypt()
    // ->pdfA(1)
    // ->chromiumBinary('upload/chrome-win/chrome.exe')
    // ->clickableLinks(true)
    ->generate();

// -------------------------------------------------------------------------
// ChromePdfDocumentTrait: merge + reprocessPdf (optional; requires fpdi+tcpdf)
// Run before inline()/download() so response headers are not sent first.
// -------------------------------------------------------------------------
if (PDF_CHROME_RUN_PIPELINE_DEMOS) {
    try {
        // Concatenate the same PDF twice (toy example: invoice + terms as separate files in real apps).
        $merged = ChromePdf::merge([$output, $output]);
        $merged->save(__DIR__ . '/../upload/pdf-chrome-merged-demo.pdf');

        // Re-run pipeline on bytes using fluent options on a fresh builder (no Chromium).
        $stamped = ChromePdf::create()
            ->textWatermark('CONFIDENTIAL', opacity: 0.12, angleDegrees: 40.0)
            ->documentMetadata(title: 'Merged demo', author: 'Support package test')
            ->reprocessPdf($merged);
        $stamped->save(__DIR__ . '/../upload/pdf-chrome-reprocessed-demo.pdf');
    } catch (ConversionFailedException $e) {
        fwrite(STDERR, "[pdfChrome] pipeline demo skipped: " . $e->getMessage() . PHP_EOL);
    }
}

$output->inline();

// $output->view();
// $output->download();
// $output->save('invoice/invoice.pdf');
