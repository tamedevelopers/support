<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use Exception;
use Tamedevelopers\Support\Capsule\File;
use Tamedevelopers\Support\Capsule\CustomException;

/**
 * ImageToText: Extract text from an image using Tesseract OCR
 *
 * Requirements:
 * - Tesseract OCR installed on the system (https://tesseract-ocr.github.io/)
 * - PHP GD extension for optional preprocessing (recommended)
 *
 * Usage examples:
 *
 * 1) From a file path
 *    $text = ImageToText::extract([
 *        'source'    => __DIR__ . '/../tests/sample.png',
 *        'language'  => 'eng',
 *        'psm'       => 6, // assume a block of text
 *        'preprocess'=> true,
 *    ]);
 *
 * 2) From an upload (e.g., $_FILES['image'])
 *    $text = ImageToText::extract([
 *        'upload' => $_FILES['image'],
 *        'language' => 'eng',
 *        'preprocess' => [
 *            'grayscale' => true,
 *            'contrast'  => 15,
 *        ],
 *    ]);
 */
class ImageToText
{
    /**
     * Extract text from an image using OCR.
     *
     * Options keys:
     * - upload: array|null  A single $_FILES[...] array for an uploaded image
     * - source: string|null Absolute path to an existing image (used if 'upload' not provided)
     * - language: string    Language code (default 'eng')
     * - engine: string      'ocrspace' (default, zero-setup via web API) | 'tesseract' | 'auto'
     * - ocrspace_api_key: string|null  API key for OCR.space; defaults to env OCR_SPACE_API_KEY or 'helloworld'
     * - psm: int|null       Tesseract page segmentation mode (--psm) [tesseract engine only]
     * - oem: int|null       Tesseract OCR engine mode (--oem) [tesseract engine only]
     * - whitelist: string|null  Characters whitelist (tessedit_char_whitelist) [tesseract engine only]
     * - tesseract_path: string|null  Path to tesseract executable; auto-detect if null [tesseract engine only]
     * - preprocess: bool|array  If true, apply default preprocessing; or pass options array
     *     - grayscale: bool (default true)
     *     - brightness: int (default 0, -255..255)
     *     - contrast: int (default 15, negative increases contrast in GD, but for simplicity we apply -abs(value))
     *     - threshold: int|null (0..255) if set, binarize image by this threshold after grayscale
     * - tmp_dir: string|null    Directory for temporary files; defaults to storage/ocr
     * - cleanup: bool           Delete temporary files after processing (default true)
     *
     * @param array $options
     * @return string Extracted text
     * @throws Exception
     */
    public static function extract(array $options = []): string
    {
        [$upload, $source, $language, $engine, $apiKey, $psm, $oem, $whitelist, $tesseractPath, $preprocess, $tmpDir, $cleanup] = [
            $options['upload'] ?? null,
            $options['source'] ?? null,
            (string)($options['language'] ?? 'eng'),
            strtolower((string)($options['engine'] ?? 'ocrspace')),
            (string)($options['ocrspace_api_key'] ?? (getenv('OCR_SPACE_API_KEY') ?: 'helloworld')),
            $options['psm'] ?? null,
            $options['oem'] ?? null,
            $options['whitelist'] ?? null,
            $options['tesseract_path'] ?? null,
            $options['preprocess'] ?? true,
            $options['tmp_dir'] ?? (\dirname(__DIR__) . '/storage/ocr'),
            (bool)($options['cleanup'] ?? true),
        ];

        // Ensure tmp dir exists
        File::makeDirectory($tmpDir);

        // Resolve input image path (from upload or given source)
        $inputPath = null;
        $tempFiles = [];

        if (is_array($upload) && isset($upload['tmp_name'])) {
            // Validate upload
            if (!isset($upload['error']) || (int)$upload['error'] !== UPLOAD_ERR_OK) {
                throw new CustomException('Image upload failed.');
            }
            $originalName = (string)($upload['name'] ?? 'upload');
            $ext = self::guessExtension((string)$upload['type'] ?? '', (string)$originalName);
            $dest = rtrim($tmpDir, '\\/') . '/' . self::uniqueName('upload', $ext);
            if (!move_uploaded_file($upload['tmp_name'], $dest)) {
                // In case move_uploaded_file fails (e.g., non-HTTP upload context), try copy
                if (!@copy($upload['tmp_name'], $dest)) {
                    throw new CustomException('Unable to store uploaded file.');
                }
            }
            $inputPath = $dest;
            $tempFiles[] = $dest;
        } elseif (is_string($source) && $source !== '') {
            if (!is_readable($source)) {
                throw new CustomException('Source image is not readable: ' . $source);
            }
            $inputPath = $source;
        } else {
            throw new CustomException('Provide either "upload" or "source" option.');
        }

        // Optional preprocessing
        if ($preprocess !== false) {
            $ppOpts = is_array($preprocess) ? $preprocess : [];
            $processed = self::preprocessImage($inputPath, $tmpDir, $ppOpts);
            if ($processed !== null) {
                $inputPath = $processed;
                $tempFiles[] = $processed;
            }
        }

        // Decide engine
        $engine = in_array($engine, ['ocrspace', 'tesseract', 'auto'], true) ? $engine : 'ocrspace';

        if ($engine === 'ocrspace' || $engine === 'auto') {
            try {
                $text = self::ocrspace($inputPath, $language, $apiKey);
                if ($cleanup) {
                    foreach ($tempFiles as $tf) { @unlink($tf); }
                }
                return $text;
            } catch (\Throwable $e) {
                if ($engine === 'ocrspace') {
                    if ($cleanup) { foreach ($tempFiles as $tf) { @unlink($tf); } }
                    throw $e; // do not fallback when explicitly requested
                }
                // else: fall through to try tesseract as a fallback for 'auto'
            }
        }

        // Tesseract engine
        $exe = self::resolveTesseractPath($tesseractPath);
        if ($exe === null) {
            if ($engine === 'tesseract') {
                if ($cleanup) { foreach ($tempFiles as $tf) { @unlink($tf); } }
                throw new CustomException('Tesseract executable not found. For zero-setup, use engine="ocrspace".');
            }
            // If 'auto' and tesseract missing, already tried ocrspace; report a friendly message
            if ($cleanup) { foreach ($tempFiles as $tf) { @unlink($tf); } }
            throw new CustomException('No OCR engine available. Tried online (ocrspace) and local (tesseract).');
        }

        // Build Tesseract command
        $cmd = [];
        $cmd[] = self::escapeArg($exe);
        $cmd[] = self::escapeArg($inputPath);
        $cmd[] = 'stdout';
        if ($language) {
            $cmd[] = '-l';
            $cmd[] = self::escapeArg($language);
        }
        if ($psm !== null) {
            $cmd[] = '--psm';
            $cmd[] = (string)(int)$psm;
        }
        if ($oem !== null) {
            $cmd[] = '--oem';
            $cmd[] = (string)(int)$oem;
        }
        if (is_string($whitelist) && $whitelist !== '') {
            $cmd[] = '-c';
            $cmd[] = self::escapeArg('tessedit_char_whitelist=' . $whitelist);
        }
        // Keep interword spaces
        $cmd[] = '-c';
        $cmd[] = self::escapeArg('preserve_interword_spaces=1');

        [$exitCode, $stdout, $stderr] = self::run($cmd);

        if ($cleanup) {
            foreach ($tempFiles as $tf) {
                @unlink($tf);
            }
        }

        if ($exitCode !== 0) {
            $msg = trim($stderr) !== '' ? trim($stderr) : 'Unknown Tesseract error (exit ' . $exitCode . ')';
            throw new CustomException($msg);
        }

        return (string)$stdout;
    }

    /**
     * Simple GD-based preprocessing: grayscale, brightness, contrast, optional threshold.
     * Returns path to processed image (PNG) or null on failure.
     *
     * @param string $srcPath
     * @param string $tmpDir
     * @param array $opts
     * @return string|null
     */
    private static function preprocessImage(string $srcPath, string $tmpDir, array $opts): ?string
    {
        if (!function_exists('imagecreatefromstring')) {
            return null; // GD unavailable
        }

        $bin = @file_get_contents($srcPath);
        if ($bin === false) {
            return null;
        }
        $im = @imagecreatefromstring($bin);
        if (!$im) {
            return null;
        }

        // Ensure alpha is preserved for PNG inputs
        imagesavealpha($im, true);

        $grayscale = (bool)($opts['grayscale'] ?? true);
        $brightness = (int)($opts['brightness'] ?? 0); // -255..255
        $contrast = (int)($opts['contrast'] ?? 15);    // we will convert to GD expected scale
        $threshold = $opts['threshold'] ?? null;       // 0..255

        if ($grayscale) {
            @imagefilter($im, IMG_FILTER_GRAYSCALE);
        }
        if ($brightness !== 0) {
            @imagefilter($im, IMG_FILTER_BRIGHTNESS, $brightness);
        }
        if ($contrast !== 0) {
            // In GD, negative values INCREASE contrast. We'll invert sign to make positive values increase contrast.
            $gdContrast = 0 - abs($contrast);
            @imagefilter($im, IMG_FILTER_CONTRAST, $gdContrast);
        }

        if ($threshold !== null) {
            $thr = max(0, min(255, (int)$threshold));
            $w = imagesx($im);
            $h = imagesy($im);
            // Manual thresholding after grayscale
            for ($y = 0; $y < $h; $y++) {
                for ($x = 0; $x < $w; $x++) {
                    $rgb = imagecolorat($im, $x, $y);
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;
                    // Since grayscale, r=g=b
                    $val = (int)round(($r + $g + $b) / 3);
                    $bw = $val >= $thr ? 255 : 0;
                    $col = imagecolorallocate($im, $bw, $bw, $bw);
                    imagesetpixel($im, $x, $y, $col);
                }
            }
        }

        $out = rtrim($tmpDir, '\\/') . '/' . self::uniqueName('preprocessed', 'png');
        imagepng($im, $out);
        imagedestroy($im);
        return $out;
    }

    /**
     * Execute a command (array of args) and capture stdout/stderr/exit code.
     *
     * @param array $args
     * @return array{0:int,1:string,2:string}
     */
    private static function run(array $args): array
    {
        $cmd = implode(' ', $args);
        $desc = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $proc = proc_open($cmd, $desc, $pipes);
        if (!\is_resource($proc)) {
            return [1, '', 'Failed to start OCR process'];
        }
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($proc);
        return [(int)$exit, (string)$stdout, (string)$stderr];
    }

    /**
     * Zero-setup online OCR using OCR.space API.
     * - Uses multipart/form-data POST with the image file.
     * - API key defaults to 'helloworld' (rate-limited/testing). Provide a real key via env OCR_SPACE_API_KEY or option.
     *
     * @throws CustomException
     */
    private static function ocrspace(string $imagePath, string $language, string $apiKey): string
    {
        if (!is_readable($imagePath)) {
            throw new CustomException('OCR input not readable.');
        }
        $endpoint = 'https://api.ocr.space/parse/image';

        // Build multipart body manually for portability
        $boundary = '----OCRSPACE-' . bin2hex(random_bytes(8));
        $eol = "\r\n";
        $body = '';

        $fields = [
            'language'       => $language,
            'isOverlayRequired' => 'false',
            'scale'          => 'true',
            'OCREngine'      => '2',
        ];

        foreach ($fields as $name => $value) {
            $body .= "--{$boundary}{$eol}";
            $body .= 'Content-Disposition: form-data; name="' . $name . '"' . $eol . $eol;
            $body .= $value . $eol;
        }

        $filename = basename($imagePath);
        $mime = mime_content_type($imagePath) ?: 'application/octet-stream';
        $fileContent = file_get_contents($imagePath);
        if ($fileContent === false) {
            throw new CustomException('Failed to read OCR input.');
        }

        $body .= "--{$boundary}{$eol}";
        $body .= 'Content-Disposition: form-data; name="file"; filename="' . $filename . '"' . $eol;
        $body .= 'Content-Type: ' . $mime . $eol . $eol;
        $body .= $fileContent . $eol;
        $body .= "--{$boundary}--{$eol}";

        $headers = [
            'Content-Type: multipart/form-data; boundary=' . $boundary,
            'apikey: ' . $apiKey,
        ];

        $opts = [
            'http' => [
                'method'  => 'POST',
                'header'  => implode("\r\n", $headers),
                'content' => $body,
                'timeout' => 60,
            ],
        ];
        $context = stream_context_create($opts);
        $response = @file_get_contents($endpoint, false, $context);
        if ($response === false) {
            $err = isset($http_response_header) ? implode('; ', (array)$http_response_header) : 'Unknown HTTP error';
            throw new CustomException('OCR request failed: ' . $err);
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            throw new CustomException('Invalid OCR response.');
        }
        if (!empty($data['IsErroredOnProcessing'])) {
            $msg = (string)($data['ErrorMessage'][0] ?? 'OCR error');
            throw new CustomException(is_array($msg) ? implode(', ', $msg) : $msg);
        }

        $parsed = $data['ParsedResults'][0]['ParsedText'] ?? '';
        return (string)$parsed;
    }

    /**
     * Auto-detect Tesseract executable path.
     */
    private static function resolveTesseractPath($provided): ?string
    {
        if (is_string($provided) && $provided !== '' && is_executable($provided)) {
            return $provided;
        }
        $candidates = [
            // Windows default install path
            'C:\\Program Files\\Tesseract-OCR\\tesseract.exe',
            'C:\\Program Files (x86)\\Tesseract-OCR\\tesseract.exe',
            // Common Unix paths
            '/usr/bin/tesseract',
            '/usr/local/bin/tesseract',
        ];
        foreach ($candidates as $cand) {
            if (is_executable($cand)) {
                return $cand;
            }
        }
        // Try on PATH
        $which = stripos(PHP_OS, 'WIN') === 0 ? 'where tesseract' : 'which tesseract';
        $out = @shell_exec($which);
        if (is_string($out)) {
            $line = trim(strtok($out, "\r\n"));
            if ($line !== '' && is_executable($line)) {
                return $line;
            }
        }
        return null;
    }

    /**
     * Simple arg escaper compatible with Windows and Unix.
     */
    private static function escapeArg(string $arg): string
    {
        // PHP implements cross-platform escaping for escapeshellarg; prefer it.
        return escapeshellarg($arg);
    }

    /**
     * Create unique filename with prefix.
     */
    private static function uniqueName(string $prefix, string $ext): string
    {
        $hash = substr(sha1(uniqid((string)mt_rand(), true)), 0, 8);
        $ext = ltrim($ext, '.');
        return $prefix . '-' . $hash . '.' . $ext;
    }

    /**
     * Guess extension from mime-type or filename; default to png.
     */
    private static function guessExtension(string $mime, string $filename): string
    {
        $map = [
            'image/png'  => 'png',
            'image/jpeg' => 'jpg',
            'image/jpg'  => 'jpg',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
            'image/bmp'  => 'bmp',
            'image/tiff' => 'tif',
        ];
        if (isset($map[$mime])) {
            return $map[$mime];
        }
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if ($ext !== '') {
            return $ext;
        }
        return 'png';
    }
}