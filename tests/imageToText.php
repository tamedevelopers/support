<?php

use Tamedevelopers\Support\Capsule\Artisan;
use Tamedevelopers\Support\Capsule\File;
use Tamedevelopers\Support\Capsule\FilesCollection;
use Tamedevelopers\Support\ImageToText;

require_once __DIR__ . '/../vendor/autoload.php';

// Simple example demonstrating ImageToText usage from CLI or Web.
//
// CLI usage:
//   php tests/imagetotext.php path/to/image.png [psm]
//
// Web usage:
//   Visit tests/imagetotext.php in your browser, upload an image, and submit.

$isCli = (php_sapi_name() === 'cli');

// :toText --path=thousand_units.png
if ($isCli) {
  $text = Artisan::call('processor:toText --path=thousand_units.png');

  dd(
    $text
  );
}


// Web mode
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $psm  = isset($_POST['psm']) && $_POST['psm'] !== '' ? (int)$_POST['psm'] : null;

    $preprocess = [
        'grayscale' => isset($_POST['grayscale']),
        'brightness'=> (int)($_POST['brightness'] ?? 0),
        'contrast'  => (int)($_POST['contrast'] ?? 15),
        'threshold' => ($_POST['threshold'] !== '' ? (int)$_POST['threshold'] : null),
    ];

    // get uploaded file
    $files = File::collect('image');

    try {
        $text = ImageToText::run([
            'upload'     => $files->first(),
            'psm'        => $psm,
            'preprocess' => $preprocess,
            'engine'     => 'auto', // 'ocrspace', 'google', 'azure', 'freeocr', 'auto'
            'emoji_friendly' => isset($_POST['emoji_friendly']),
            // 'tesseract_path' => 'C:\Program Files\Tesseract-OCR\tesseract.exe'
        ]);

        header('Content-Type: text/plain; charset=utf-8');
        echo $text;
        exit;
    } catch (\Throwable $e) {
        $error = $e->getMessage();
    }
}

$error = $error ?? null;
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <title>ImageToText Demo</title>
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; margin: 2rem; }
    fieldset { max-width: 680px; }
    .row { margin-bottom: .75rem; }
    label { display: inline-block; width: 130px; }
    input[type="number"] { width: 100px; }
    </style>
    <?= File::publishJS();?>
</head>
<body>
  <h1>ImageToText (Tesseract OCR) Demo</h1>
  <?php if ($error): ?>
    <p style="color:#c00;"><strong>Error:</strong> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>
  <form method="post" enctype="multipart/form-data">
    <fieldset>
      <legend>Upload an image</legend>
      <div class="row">
        <label for="image">Image</label>
        <input id="image" type="file" name="image" accept="image/*" multiple/>
      </div>
      <div class="row">
        <label for="psm">PSM</label>
        <input id="psm" type="number" name="psm" min="0" max="13" placeholder="6" />
      </div>
      <div class="row">
        <label>Options</label>
        <label><input type="checkbox" name="grayscale" checked /> grayscale</label>
        <label style="margin-left:1rem;"><input type="checkbox" name="emoji_friendly" /> emoji-friendly (keep color, broader detection)</label>
      </div>
      <div class="row">
        <label for="brightness">Brightness</label>
        <input id="brightness" type="number" name="brightness" value="0" min="-255" max="255" />
      </div>
      <div class="row">
        <label for="contrast">Contrast</label>
        <input id="contrast" type="number" name="contrast" value="20" min="0" max="255" />
      </div>
      <div class="row">
        <label for="threshold">Threshold</label>
        <input id="threshold" type="number" name="threshold" value="" min="0" max="255" placeholder="(optional)" />
      </div>
      <div class="row">
        <button type="submit">Extract Text</button>
      </div>
    </fieldset>
  </form>

  <p style="margin-top:2rem;color:#666;">Language / script is autodetected by each OCR engine. For colored emoji or UI screenshots, enable emoji-friendly mode.</p>
</body>
</html>