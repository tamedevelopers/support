<?php

use Tamedevelopers\Support\ImageToText;

require_once __DIR__ . '/../vendor/autoload.php';

// Simple example demonstrating ImageToText usage from CLI or Web.
//
// CLI usage:
//   php tests/imagetotext.php path/to/image.png [lang] [psm]
//
// Web usage:
//   Visit tests/imagetotext.php in your browser, upload an image, and submit.

$isCli = (php_sapi_name() === 'cli');

if ($isCli) {
    $path = $argv[1] ?? '';
    $lang = $argv[2] ?? 'eng';
    $psm  = isset($argv[3]) ? (int)$argv[3] : null;

    if ($path === '' || !is_readable($path)) {
        fwrite(STDERR, "Usage: php tests/imagetotext.php <image_path> [lang=eng] [psm]\n");
        exit(1);
    }

    try {
        $text = ImageToText::extract([
            'source'     => $path,
            'language'   => $lang,
            'psm'        => $psm,
            'preprocess' => [
                'grayscale' => true,
                'contrast'  => 20,
                // 'threshold' => 170, // optionally enable thresholding
            ],
        ]);
        echo $text, "\n";
    } catch (\Throwable $e) {
        fwrite(STDERR, 'OCR error: ' . $e->getMessage() . "\n");
        exit(2);
    }
    exit(0);
}

// Web mode
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lang = isset($_POST['lang']) && $_POST['lang'] !== '' ? (string)$_POST['lang'] : 'eng';
    $psm  = isset($_POST['psm']) && $_POST['psm'] !== '' ? (int)$_POST['psm'] : null;

    $preprocess = [
        'grayscale' => isset($_POST['grayscale']),
        'brightness'=> (int)($_POST['brightness'] ?? 0),
        'contrast'  => (int)($_POST['contrast'] ?? 15),
        'threshold' => ($_POST['threshold'] !== '' ? (int)$_POST['threshold'] : null),
    ];

    try {
        $text = ImageToText::extract([
            'upload'     => $_FILES['image'] ?? null,
            'language'   => $lang,
            'psm'        => $psm,
            'preprocess' => $preprocess,
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
        <input id="image" type="file" name="image" accept="image/*" required />
      </div>
      <div class="row">
        <label for="lang">Language</label>
        <input id="lang" type="text" name="lang" value="eng" placeholder="eng" />
      </div>
      <div class="row">
        <label for="psm">PSM</label>
        <input id="psm" type="number" name="psm" min="0" max="13" placeholder="6" />
      </div>
      <div class="row">
        <label>Preprocess</label>
        <label><input type="checkbox" name="grayscale" checked /> grayscale</label>
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

  <p style="margin-top:2rem;color:#666;">Note: Requires Tesseract OCR installed and available on PATH, or configure a custom path in the class options.</p>
</body>
</html>