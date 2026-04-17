<?php

declare(strict_types=1);


use Tamedevelopers\Support\ChromePdf\ChromePdf;
use Tamedevelopers\Support\ChromePdf\Exception\ConversionFailedException;

require_once __DIR__ . '/../vendor/autoload.php';

/** Set true to run merge + reprocessPdf demos after the main PDF (writes under upload/). */
const PDF_CHROME_RUN_PIPELINE_DEMOS = false;


$files = [
    '1' => 'upload/template.html',
    '2' => base_path('upload/template2.html'),
    '3' => base_path('upload/template3.html'),
];

$output = ChromePdf::create()
    // ->fromFile('upload/template.html')
    ->fromUrl('https://www.lhkexpress.com')
    // ->clickableLinks(false)
    // ->cssFile('upload/style2.css')
    // ->margin(20)
    ->printFromElement('.body')
    ->textWatermark('CONFIDENTIAL')
    ->encrypt(
        userPassword: 'user', 
        ownerPassword: 'owner', 
        blockedPermissions: ['copy', 'print'],
    )
    // ->chromiumBinary('upload/chrome-win/chrome.exe')
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
