<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Traits;

trait ImageToTextTrait{

    /**
     * Image preprocessing for better OCR results
     */
    private static function preprocessImage(string $srcPath, string $tmpDir, array $opts): ?string
    {
        if (!function_exists('imagecreatefromstring')) {
            return null;
        }

        $bin = @file_get_contents($srcPath);
        if ($bin === false) return null;

        $im = @imagecreatefromstring($bin);
        if (!$im) return null;

        imagesavealpha($im, true);

        if ($opts['grayscale'] ?? true) {
            imagefilter($im, IMG_FILTER_GRAYSCALE);
        }

        if (($opts['adaptive_brightness'] ?? false) === true) {
            self::applyAdaptiveBrightness($im);
        }

        if (($brightness = $opts['brightness'] ?? 0) !== 0) {
            imagefilter($im, IMG_FILTER_BRIGHTNESS, $brightness);
        }
        if (($contrast = $opts['contrast'] ?? 12) !== 0) {
            imagefilter($im, IMG_FILTER_CONTRAST, -abs($contrast));
        }

        // Threshold binarization
        if (($threshold = $opts['threshold'] ?? null) !== null) {
            $thr = max(0, min(255, (int)$threshold));
            $w = imagesx($im); $h = imagesy($im);
            for ($y = 0; $y < $h; $y++) {
                for ($x = 0; $x < $w; $x++) {
                    $rgb = imagecolorat($im, $x, $y);
                    $r = ($rgb >> 16) & 0xFF; $g = ($rgb >> 8) & 0xFF; $b = $rgb & 0xFF;
                    $val = (int)round(($r + $g + $b) / 3);
                    $bw = $val >= $thr ? 255 : 0;
                    $col = imagecolorallocate($im, $bw, $bw, $bw);
                    imagesetpixel($im, $x, $y, $col);
                }
            }
        }

        $out = rtrim($tmpDir, '/\\') . '/' . self::uniqueName('preprocessed', 'png');
        imagepng($im, $out);
        unset($im);
        return $out;
    }

    /**
     * Nudge very dark or extremely bright scans toward mid-tones so OCR sees strokes more clearly.
     */
    private static function applyAdaptiveBrightness($im): void
    {
        $mean = self::sampleMeanLuminance($im);
        if ($mean < 88.0) {
            $delta = (int) min(28, max(6, (88.0 - $mean) * 0.45));
            imagefilter($im, IMG_FILTER_BRIGHTNESS, $delta);
        } elseif ($mean > 248.0) {
            $delta = (int) max(-10, (248.0 - $mean) * 0.5);
            if ($delta !== 0) {
                imagefilter($im, IMG_FILTER_BRIGHTNESS, $delta);
            }
        }
    }

    private static function sampleMeanLuminance($im): float
    {
        $w = imagesx($im);
        $h = imagesy($im);
        if ($w < 1 || $h < 1) {
            return 128.0;
        }
        $stepX = max(1, (int) ($w / 80));
        $stepY = max(1, (int) ($h / 80));
        $sum = 0.0;
        $n = 0;
        for ($y = 0; $y < $h; $y += $stepY) {
            for ($x = 0; $x < $w; $x += $stepX) {
                $rgb = imagecolorat($im, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $sum += ($r + $g + $b) / 3;
                $n++;
            }
        }

        return $n > 0 ? $sum / $n : 128.0;
    }

    /**
     * Helper methods (keep your existing implementations)
     */
    private static function resolveTesseractPath($provided): ?string
    {
        if (is_string($provided) && !empty($provided) && is_executable($provided)) {
            return $provided;
        }
        $candidates = [
            'C:\\Program Files\\Tesseract-OCR\\tesseract.exe',
            'C:\\Program Files (x86)\\Tesseract-OCR\\tesseract.exe',
            '/usr/bin/tesseract', 
            '/usr/local/bin/tesseract',
        ];
        foreach ($candidates as $cand) {
            if (is_executable($cand)) return $cand;
        }
        $which = stripos(PHP_OS, 'WIN') === 0 ? 'where tesseract' : 'which tesseract';
        $out = @shell_exec($which);
        if ($out && ($line = trim(strtok($out, "\r\n")))) {
            return is_executable($line) ? $line : null;
        }

        return null;
    }

    private static function executeCommand(array $args): array
    {
        $cmd = implode(' ', $args);
        $desc = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $proc = proc_open($cmd, $desc, $pipes);
        if (!\is_resource($proc)) {
            return [1, '', 'Failed to start process'];
        }
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        return [proc_close($proc), self::ensureUtf8OcrOutput((string) $stdout), (string) $stderr];
    }

    /**
     * Tesseract may emit non–UTF-8 on some Windows locales; normalize for downstream use.
     */
    private static function ensureUtf8OcrOutput(string $s): string
    {
        if ($s === '' || mb_check_encoding($s, 'UTF-8')) {
            return $s;
        }
        $converted = @mb_convert_encoding($s, 'UTF-8', 'Windows-1252');
        if ($converted !== false && mb_check_encoding($converted, 'UTF-8')) {
            return $converted;
        }
        $latin = @mb_convert_encoding($s, 'UTF-8', 'ISO-8859-1');
        return ($latin !== false && mb_check_encoding($latin, 'UTF-8')) ? $latin : $s;
    }

    /**
     * When OCR language is fully autodetect, prefer multilingual traineddata if present (e.g. chi_sim+eng for Han + Latin).
     */
    private static function inferTesseractAutodetectLangPack(string $exe): string
    {
        $dir = dirname($exe);
        $tessdata = $dir . DIRECTORY_SEPARATOR . 'tessdata';
        if (!is_dir($tessdata)) {
            return '';
        }
        $has = static function (string $name) use ($tessdata): bool {
            return is_file($tessdata . DIRECTORY_SEPARATOR . $name . '.traineddata');
        };
        if ($has('chi_sim') && $has('eng')) {
            return 'chi_sim+eng';
        }
        if ($has('chi_tra') && $has('eng')) {
            return 'chi_tra+eng';
        }
        if ($has('jpn') && $has('eng')) {
            return 'jpn+eng';
        }
        if ($has('kor') && $has('eng')) {
            return 'kor+eng';
        }
        if ($has('ara') && $has('eng')) {
            return 'ara+eng';
        }

        return '';
    }

    private static function escapeArg(string $arg): string
    {
        return escapeshellarg($arg);
    }

    private static function uniqueName(string $prefix, string $ext): string
    {
        return $prefix . '-' . substr(sha1(uniqid((string)mt_rand(), true)), 0, 8) . '.' . ltrim($ext, '.');
    }

    private static function guessExtension(string $mime, string $filename): string
    {
        $map = [
            'image/png' => 'png', 'image/jpeg' => 'jpg', 'image/jpg' => 'jpg',
            'image/gif' => 'gif', 'image/webp' => 'webp', 'image/bmp' => 'bmp', 'image/tiff' => 'tif',
        ];
        return $map[$mime] ?? pathinfo($filename, PATHINFO_EXTENSION) ?: 'png';
    }

    private static function cleanupFiles(array $files): void
    {
        foreach ($files as $file) {
            @unlink($file);
        }
    }

}