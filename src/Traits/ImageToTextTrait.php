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

        // Apply filters
        if ($opts['grayscale'] ?? true) {
            imagefilter($im, IMG_FILTER_GRAYSCALE);
        }
        if (($brightness = $opts['brightness'] ?? 0) !== 0) {
            imagefilter($im, IMG_FILTER_BRIGHTNESS, $brightness);
        }
        if (($contrast = $opts['contrast'] ?? 15) !== 0) {
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
        imagedestroy($im);
        return $out;
    }

    /**
     * Helper methods (keep your existing implementations)
     */
    private static function resolveTesseractPath($provided): ?string
    {
        if (is_string($provided) && $provided !== '' && is_executable($provided)) {
            return $provided;
        }
        $candidates = [
            'C:\\Program Files\\Tesseract-OCR\\tesseract.exe',
            'C:\\Program Files (x86)\\Tesseract-OCR\\tesseract.exe',
            '/usr/bin/tesseract', '/usr/local/bin/tesseract',
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
        return [proc_close($proc), (string)$stdout, (string)$stderr];
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