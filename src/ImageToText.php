<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use Tamedevelopers\Support\Capsule\File;
use Tamedevelopers\Support\Capsule\CustomException;
use Tamedevelopers\Support\Traits\ImageToTextTrait;
use Tamedevelopers\Support\Traits\OcrLanguageTrait;

/**
 * ImageToText: Extract text from images using multiple OCR engines
 * 
 * Supported Engines:
 * - auto: Try ocrspace then tesseract, google, … (default when engine omitted)
 * - ocrspace: Free online OCR with 1MB limit
 * - tesseract: Local OCR engine (requires installation)
 * - google: Google Cloud Vision OCR (free tier: 1000 units/month)
 * - azure: Microsoft Azure Computer Vision (free tier: 5000 transactions/month)
 * - freeocr: FreeOCR.com API (no API key required, limited)
 * - auto: Try all engines in order until one works
 *
 * Usage examples:
 *
 * 1) From uploaded file — omit engine and ocr_language; backends autodetect script/language.
 *    $text = ImageToText::run(['upload' => 'image_name']);
 *
 */
class ImageToText
{
    use ImageToTextTrait;
    use OcrLanguageTrait;

    /**
     * Supported OCR engines (default selection is 'auto', not this list order).
     */
    private const ENGINES = ['tesseract', 'ocrspace', 'google', 'azure', 'freeocr', 'auto'];

    /**
     * Order for engine=auto: online OCR first handles Han + Latin without local chi_sim; Tesseract is fallback.
     *
     * @var list<string>
     */
    private const AUTO_ENGINE_TRY_ORDER = ['ocrspace', 'tesseract', 'google', 'azure', 'freeocr'];

    /**
     * Extract text from an image using OCR
     *
     * Options:
     * - upload: string            Uploaded file input name
     * - source: string|null       Path to existing image file
     * - google_detection_feature: string  TEXT_DETECTION | DOCUMENT_TEXT_DETECTION (default: DOCUMENT_TEXT_DETECTION).
     * - engine: string            OCR engine (default: 'auto')
     * - max_file_size: int        Maximum file size in bytes (default: 5MB)
     * - tmp_dir: string|null      Temporary directory for processing
     * - cleanup: bool             Delete temporary files (default: true)
     * - preprocess: bool|array    Preprocess (false = skip). Defaults: contrast 12, adaptive_brightness for dark/very bright photos. Override keys: grayscale, brightness, contrast, threshold, adaptive_brightness.
     * - ocr_language: string      Optional override; omit for full autodetect (recommended).
     * - language: string          Alias for ocr_language
     * 
     * Engine-specific options:
     * - ocrspace_api_key: string  OCR.space API key
     * - google_api_key: string    Google Cloud Vision API key
     * - azure_api_key: string     Azure Computer Vision API key
     * - azure_endpoint: string    Azure service endpoint
     * - tesseract_path: string    Path to tesseract executable
     * - tesseract_psm: int        Page segmentation mode
     * - tesseract_oem: int        OCR engine mode
     * - tesseract_whitelist: string Character whitelist
     * - ocrspace_ocr_engine: int   OCR.space OCREngine 1 or 2 (default 2, original API default).
     *
     * @param array $options
     * @return string
     * @throws CustomException
     */
    public static function run(array $options = []): string
    {
        // Extract and validate options
        $config = self::validateOptions($options);
        
        // Resolve input image path
        $inputData = self::resolveInput($config);
        $inputPath = $inputData['path'];
        $tempFiles = $inputData['tempFiles'];

        try {
            if ($config['preprocess'] !== false) {
                $userPre = is_array($config['preprocess']) ? $config['preprocess'] : [];
                $preOpts = array_merge([
                    'grayscale' => true,
                    'brightness' => 0,
                    'contrast' => 12,
                    'threshold' => null,
                    'adaptive_brightness' => true,
                ], $userPre);

                $processed = self::preprocessImage($inputPath, $config['tmp_dir'], $preOpts);
                if ($processed !== null) {
                    $inputPath = $processed;
                    $tempFiles[] = $processed;
                }
            }

            // Process with selected engine
            $text = self::processWithEngine($inputPath, $config, $tempFiles);

            // Cleanup temporary files
            if ($config['cleanup']) {
                self::cleanupFiles($tempFiles);
            }

            return self::normalizeOcrText($text);

        } catch (\Throwable $e) {
            // Ensure cleanup on failure
            if ($config['cleanup']) {
                self::cleanupFiles($tempFiles);
            }
            throw $e;
        }
    }

    /**
     * Validate and extract configuration options
     */
    private static function validateOptions(array $options): array
    {
        $engine = strtolower($options['engine'] ?? 'auto');
        if (!in_array($engine, self::ENGINES, true)) {
            throw new CustomException("Unsupported engine: {$engine}. Supported: " . implode(', ', self::ENGINES));
        }

        $gdf = strtoupper((string) ($options['google_detection_feature'] ?? 'DOCUMENT_TEXT_DETECTION'));
        if (!in_array($gdf, ['TEXT_DETECTION', 'DOCUMENT_TEXT_DETECTION'], true)) {
            $gdf = 'DOCUMENT_TEXT_DETECTION';
        }

        $config = [
            'upload' => $options['upload'] ?? null,
            'source' => $options['source'] ?? null,
            'engine' => $engine,
            'max_file_size' => $options['max_file_size'] ?? Tame()->sizeToBytes('2mb'),
            'tmp_dir' => $options['tmp_dir'] ?? (\dirname(__DIR__) . '/storage/ocr'),
            'cleanup' => (bool) ($options['cleanup'] ?? true),
            'preprocess' => $options['preprocess'] ?? true,
            'google_detection_feature' => $gdf,

            // Engine-specific configurations
            'ocrspace_api_key' => $options['ocrspace_api_key'] ?? (env('OCR_SPACE_API_KEY', 'helloworld')),
            'google_api_key' => $options['google_api_key'] ?? env('GOOGLE_VISION_API_KEY'),
            'azure_api_key' => $options['azure_api_key'] ?? env('AZURE_VISION_KEY'),
            'azure_endpoint' => $options['azure_endpoint'] ?? env('AZURE_VISION_ENDPOINT'),
            'tesseract_path' => $options['tesseract_path'] ?? null,
            'tesseract_psm' => $options['tesseract_psm'] ?? $options['psm'] ?? null,
            'tesseract_oem' => $options['tesseract_oem'] ?? $options['oem'] ?? null,
            'tesseract_whitelist' => $options['tesseract_whitelist'] ?? $options['whitelist'] ?? null,
            'ocrspace_ocr_engine' => max(1, min(2, (int) ($options['ocrspace_ocr_engine'] ?? 2))),
        ];

        $ocrLang = (string) ($options['ocr_language'] ?? $options['language'] ?? '');
        $config['_ocr'] = self::expandOcrLanguageForEngines($ocrLang);

        return $config;
    }

    /**
     * Resolve input image path from upload or source
     */
    private static function resolveInput(array $config): array
    {
        File::makeDirectory($config['tmp_dir']);

        $tempFiles      = [];
        $maxFileSize    = $config['max_file_size'];
        
        $file = File::collect($config['upload'] ?? 'image');
        $upload = $file->first();

        if ($upload && $upload->isNotEmpty()) {
            // Validate uploaded file
            if (!$upload->noError()) {
                throw new CustomException('Image upload failed.');
            }

            // Check file size
            if ($maxFileSize > 0 && $upload->size() > $maxFileSize) {
                $maxMB = round($maxFileSize / 1024 / 1024, 2);
                $actualMB = round($upload->size() / 1024 / 1024, 2);
                throw new CustomException("File size exceeds {$maxMB} MB limit. Actual: {$actualMB} MB.");
            }

            $upload->generate();
            $uploaded = $upload->move($config['tmp_dir']);
            
            if (!$uploaded['status']) {
                throw new CustomException('Unable to store uploaded file.');
            }

            return ['path' => $uploaded['path'], 'tempFiles' => [$uploaded['path']]];

        } elseif (is_string($config['source']) && !empty($config['source'])) {
            // Validate source file
            if (!@is_readable($config['source'])) {
                throw new CustomException('Source image is not readable: ' . $config['source']);
            }

            // Check file size
            if ($maxFileSize > 0) {
                $fileSize = filesize($config['source']);
                if ($fileSize > $maxFileSize) {
                    $maxMB = round($maxFileSize / 1024 / 1024, 2);
                    $actualMB = round($fileSize / 1024 / 1024, 2);
                    throw new CustomException("File size exceeds {$maxMB} MB limit. Actual: {$actualMB} MB.");
                }
            }

            return ['path' => $config['source'], 'tempFiles' => []];

        } else {
            throw new CustomException('Provide either "upload" or "source" option.');
        }
    }

    /**
     * Process image with selected OCR engine
     */
    private static function processWithEngine(string $imagePath, array $config, array $tempFiles): string
    {
        $engine = $config['engine'];
        
        if ($engine === 'auto') {
            return self::autoEngine($imagePath, $config, $tempFiles);
        }

        switch ($engine) {
            case 'ocrspace':
                return self::ocrspaceEngine(
                    $imagePath,
                    $config['_ocr']['ocrspace'],
                    $config['ocrspace_api_key'],
                    (int) $config['ocrspace_ocr_engine']
                );
            case 'tesseract':
                return self::tesseractEngine($imagePath, $config);
            case 'google':
                return self::googleEngine($imagePath, $config);
            case 'azure':
                return self::azureEngine($imagePath, $config['_ocr']['azure'], $config['azure_api_key'], $config['azure_endpoint']);
            case 'freeocr':
                return self::freeocrEngine($imagePath, $config['_ocr']['ocrspace']);
            default:
                throw new CustomException("Unsupported engine: {$engine}");
        }
    }

    /**
     * Try all engines in order until one works
     */
    private static function autoEngine(string $imagePath, array $config, array $tempFiles): string
    {
        $lastException = null;

        foreach (self::AUTO_ENGINE_TRY_ORDER as $engine) {
            try {
                $config['engine'] = $engine;
                return self::processWithEngine($imagePath, $config, $tempFiles);
            } catch (\Throwable $e) {
                $lastException = $e;
                // Continue to next engine
            }
        }

        throw new CustomException('All OCR engines failed. Last error: ' . $lastException->getMessage());
    }

    /**
     * OCR.space Online OCR Engine
     */
    private static function ocrspaceEngine(string $imagePath, string $language, string $apiKey, int $ocrEngine = 2): string
    {
        if (!@is_readable($imagePath)) {
            throw new CustomException('OCR input not readable.');
        }

        $endpoint = 'https://api.ocr.space/parse/image';
        $boundary = '----OCRSPACE-' . bin2hex(random_bytes(8));
        $eol = "\r\n";
        $body = '';

        $ocrEngine = max(1, min(2, $ocrEngine));

        $fields = [
            'language' => $language,
            'isOverlayRequired' => 'false',
            'scale' => 'true',
            'OCREngine' => (string) $ocrEngine,
        ];

        foreach ($fields as $name => $value) {
            $body .= "--{$boundary}{$eol}";
            $body .= "Content-Disposition: form-data; name=\"{$name}\"{$eol}{$eol}";
            $body .= "{$value}{$eol}";
        }

        $filename = basename($imagePath);
        $mime = mime_content_type($imagePath) ?: 'application/octet-stream';
        $fileContent = file_get_contents($imagePath);
        if ($fileContent === false) {
            throw new CustomException('Failed to read OCR input.');
        }

        $body .= "--{$boundary}{$eol}";
        $body .= "Content-Disposition: form-data; name=\"file\"; filename=\"{$filename}\"{$eol}";
        $body .= "Content-Type: {$mime}{$eol}{$eol}";
        $body .= $fileContent . $eol;
        $body .= "--{$boundary}--{$eol}";

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: multipart/form-data; boundary={$boundary}\r\n" .
                           "apikey: {$apiKey}",
                'content' => $body,
                'timeout' => 60,
            ],
        ]);

        $response = @file_get_contents($endpoint, false, $context);
        if ($response === false) {
            throw new CustomException('OCR.space API request failed.');
        }

        $result = json_decode($response, true);
        if (!is_array($result)) {
            throw new CustomException('Invalid OCR.space response.');
        }

        if (!empty($result['IsErroredOnProcessing'])) {
            $error = $result['ErrorMessage'][0] ?? 'Unknown OCR error';
            throw new CustomException('OCR.space error: ' . $error);
        }

        return $result['ParsedResults'][0]['ParsedText'] ?? '';
    }

    /**
     * Tesseract Local OCR Engine
     */
    private static function tesseractEngine(string $imagePath, array $config): string
    {
        $exe = self::resolveTesseractPath($config['tesseract_path']);

        if ($exe === null) {
            throw new CustomException('Tesseract executable not found. Install it or use another engine.');
        }

        $cmd = [self::escapeArg($exe), self::escapeArg($imagePath), 'stdout'];
        
        $tessLang = $config['_ocr']['tesseract'] ?? '';
        if ($tessLang === '') {
            $tessLang = self::inferTesseractAutodetectLangPack($exe);
        }
        if ($tessLang !== '') {
            array_push($cmd, '-l', self::escapeArg($tessLang));
        }
        if ($config['tesseract_psm'] !== null) {
            array_push($cmd, '--psm', (string)(int)$config['tesseract_psm']);
        }
        if ($config['tesseract_oem'] !== null) {
            array_push($cmd, '--oem', (string)(int)$config['tesseract_oem']);
        }
        if ($config['tesseract_whitelist']) {
            array_push($cmd, '-c', self::escapeArg('tessedit_char_whitelist=' . $config['tesseract_whitelist']));
        }

        array_push($cmd, '-c', self::escapeArg('preserve_interword_spaces=1'));

        [$exitCode, $stdout, $stderr] = self::executeCommand($cmd);

        if ($exitCode !== 0) {
            throw new CustomException('Tesseract error: ' . (trim($stderr) ?: "Exit code {$exitCode}"));
        }

        return trim($stdout);
    }

    /**
     * Google Cloud Vision OCR Engine
     */
    private static function googleEngine(string $imagePath, array $config): string
    {
        $apiKey = $config['google_api_key'];
        if (!$apiKey) {
            throw new CustomException('Google Vision API key required. Set google_api_key option or GOOGLE_VISION_API_KEY environment variable.');
        }

        $imageContent = base64_encode((string) file_get_contents($imagePath));

        $hints = array_values(array_unique($config['_ocr']['google_hints'] ?? []));

        $request = [
            'image' => ['content' => $imageContent],
            'features' => [[
                'type' => $config['google_detection_feature'],
                'maxResults' => 50,
            ]],
        ];
        if ($hints !== []) {
            $request['imageContext'] = ['languageHints' => $hints];
        }

        $data = ['requests' => [$request]];

        $url = "https://vision.googleapis.com/v1/images:annotate?key={$apiKey}";

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($data),
                'timeout' => 30,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            throw new CustomException('Google Vision API request failed.');
        }

        $result = json_decode($response, true);
        if (!is_array($result)) {
            throw new CustomException('Invalid Google Vision API response.');
        }
        $first = $result['responses'][0] ?? null;
        if (!is_array($first)) {
            return '';
        }
        if (!empty($first['error'])) {
            $msg = $first['error']['message'] ?? json_encode($first['error']);
            throw new CustomException('Google Vision API error: ' . $msg);
        }

        $text = $first['fullTextAnnotation']['text'] ?? '';
        if ($text !== '') {
            return $text;
        }
        foreach ($first['textAnnotations'] ?? [] as $i => $ann) {
            if ($i === 0 && isset($ann['description'])) {
                return (string) $ann['description'];
            }
        }

        return '';
    }

    /**
     * Microsoft Azure Computer Vision OCR Engine
     */
    private static function azureEngine(string $imagePath, string $language, ?string $apiKey, ?string $endpoint): string
    {
        if (!$apiKey) {
            throw new CustomException('Azure Vision key required. Set azure_api_key option or AZURE_VISION_KEY environment variable.');
        }

        $endpoint = $endpoint ?: 'https://{your-region}.api.cognitive.microsoft.com/';
        $url = rtrim($endpoint, '/') . '/vision/v3.2/ocr?language=' . $language;
        $imageData = file_get_contents($imagePath);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/octet-stream\r\n" .
                           "Ocp-Apim-Subscription-Key: {$apiKey}",
                'content' => $imageData,
                'timeout' => 30
            ]
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            throw new CustomException('Azure Vision API request failed.');
        }

        $result = json_decode($response, true);
        $text = '';

        foreach ($result['regions'] ?? [] as $region) {
            foreach ($region['lines'] ?? [] as $line) {
                foreach ($line['words'] ?? [] as $word) {
                    $text .= $word['text'] . ' ';
                }
                $text .= "\n";
            }
        }

        return trim($text);
    }

    /**
     * FreeOCR.com API Engine
     */
    private static function freeocrEngine(string $imagePath, string $language): string
    {
        $endpoint = 'https://api.freeocrapi.com/v1/ocr';
        $boundary = '----' . uniqid();
        $eol = "\r\n";
        $body = '';

        // Build multipart form
        $body .= "--{$boundary}{$eol}";
        $body .= "Content-Disposition: form-data; name=\"language\"{$eol}{$eol}";
        $body .= "{$language}{$eol}";
        
        $body .= "--{$boundary}{$eol}";
        $body .= "Content-Disposition: form-data; name=\"image\"; filename=\"" . basename($imagePath) . "\"{$eol}";
        $body .= "Content-Type: " . (mime_content_type($imagePath) ?: 'application/octet-stream') . "{$eol}{$eol}";
        $body .= file_get_contents($imagePath) . $eol;
        $body .= "--{$boundary}--{$eol}";

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: multipart/form-data; boundary={$boundary}",
                'content' => $body,
                'timeout' => 30
            ]
        ]);

        $response = @file_get_contents($endpoint, false, $context);
        if ($response === false) {
            throw new CustomException('FreeOCR API request failed.');
        }

        $result = json_decode($response, true);
        return $result['text'] ?? '';
    }

    /**
     * Trim and NFC-normalize OCR output when intl is available (stable composed Hangul, etc.).
     */
    private static function normalizeOcrText(string $text): string
    {
        $text = trim($text);
        if ($text === '' || !class_exists(\Normalizer::class)) {
            return $text;
        }
        $norm = \Normalizer::normalize($text, \Normalizer::FORM_C);

        return is_string($norm) ? $norm : $text;
    }
}