<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Traits;

use SVG\SVG;
use Tamedevelopers\Support\Capsule\File;
use Tamedevelopers\Support\Capsule\Manager;
use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Tame;

trait QRCodeTrait
{   

    /**
     * Set output pattern mode.
     * 
     * @param 'classic'|'rounded'|'thin'|'smooth'|'circle'|'leaf'|'inverted'|'pillow'|'finder'|'scallop' $pattern
     * @return string
     */
    protected function resolvePatterns($pattern)
    {
        $pattern = Str::lower($pattern);

        if($pattern === 'classic'){
            $this->shape['shape']       = 'square';
            $this->outerShape['shape']  = $this->shape['shape'];
            $this->innerShape['shape']  = $this->outerShape['shape'];
        } elseif($pattern === 'rounded'){
            $this->shape['shape']       = $pattern;
            $this->outerShape['shape']  = $this->shape['shape'];
            $this->innerShape['shape']  = $this->outerShape['shape'];
        } elseif($pattern === 'thin'){
            $this->shape['shape']       = 'thin';
            $this->outerShape['shape']  = 'square';
            $this->innerShape['shape']  = $this->outerShape['shape'];
        } elseif($pattern === 'smooth'){
            $this->shape['shape']       = 'smooth';
            $this->outerShape['shape']  = 'rounded';
            $this->innerShape['shape']  = $this->outerShape['shape'];
        } elseif($pattern === 'circle'){
            $this->shape['shape']       = 'smooth';
            $this->outerShape['shape']  = 'rounded';
            $this->innerShape['shape']  = 'circle';
        } elseif ($pattern === 'leaf') {
            $this->shape['shape']      = 'square';
            $this->outerShape['shape'] = 'leaf';
            $this->innerShape['shape'] = 'rounded';
        } elseif ($pattern === 'inverted') {
            $this->shape['shape']      = 'square';
            $this->outerShape['shape'] = 'inverted';
            $this->innerShape['shape'] = 'inverted';
        } elseif ($pattern === 'pillow') {
            $this->shape['shape']      = 'square';
            $this->outerShape['shape'] = 'pillow';
            $this->innerShape['shape'] = 'rounded';
        } elseif ($pattern === 'finder') {
            $this->shape['shape']      = 'square';
            $this->outerShape['shape'] = 'rounded';
            $this->innerShape['shape'] = 'square';
        } elseif ($pattern === 'scallop') {
            $this->shape['shape']      = 'square';
            $this->outerShape['shape'] = 'scallop';
            $this->innerShape['shape'] = 'rounded';
        }

        return $pattern;
    }

    /**
     * Set template with patterns
     * 
     * @param string $template
     * @return string
     */
    protected function resolveTemplates($template)
    {
        $template = Str::lower($template);

        match($template){
            'neon' => $this->resolvePatterns('smooth'),
            'forest' => $this->resolvePatterns('smooth'),
            'instagram' => $this->resolvePatterns('rounded'),
            'facebook' => $this->resolvePatterns('rounded'),
            'youtube' => $this->resolvePatterns('circle'),
            'whatsapp' => $this->resolvePatterns('thin'),
            'telegram' => $this->resolvePatterns('thin'),
            'snapchat' => $this->resolvePatterns('circle'),
            default => $this->resolvePatterns('classic')
        };

        return $template;
    }

    /**
     * Retrieve color palette settings.
     */
    protected function getPaletteColors(): array
    {
        return match ($this->template) {
            'facebook'  => ['module' => '#000000', 'finder' => '#1877F2', 'bg' => '#FFFFFF'],
            'instagram' => ['module' => '#B81A7D', 'finder' => '#7A32BD', 'bg' => '#FFFFFF'],
            'youtube'   => ['module' => '#000000', 'finder' => '#FF0000', 'bg' => '#FFFFFF'],
            'whatsapp'  => ['module' => '#075E54', 'finder' => '#25D366', 'bg' => '#FFFFFF'],
            'telegram'  => ['module' => '#229ED9', 'finder' => '#0088CC', 'bg' => '#FFFFFF'],
            'snapchat'  => ['module' => '#1a1919', 'finder' => '#FFFC00', 'bg' => '#FFFFFF'],
            'forest'    => ['module' => '#1E7E34', 'finder' => '#1E7E34', 'bg' => '#FFFFFF'],
            'neon'      => ['module' => '#7B1FA2', 'finder' => '#7B1FA2', 'bg' => '#FFFFFF'],
            default     => ['module' => '#000000', 'finder' => '#000000', 'bg' => '#FFFFFF'],
        };
    }

    /**
     * Inject center icon SVG/PNG into the generated QR code SVG markup.
     *
     * @param string $svgOutput Raw-SVG markup from renderer
     * @return string Modified svg markup with overlayed center icon
     */
    protected function applyCenterIcon(string $svgOutput): string
    {
        if (!$this->showIcon) {
            return $svgOutput;
        }

        // Use custom icon path if provided, otherwise resolve via platform
        if(!empty($this->iconPath)){
            $iconPath = Tame::stringReplacer($this->iconPath);
        } else{
            $iconPath = (!empty($this->platform) ? Tame::platformIcon($this->platform) : null);
        }

        // clean path
        $iconPath = File::cleanPath($iconPath);

        if (empty($iconPath) || !File::exists($iconPath)) {
            return $svgOutput;
        }

        $iconContent = File::get($iconPath);
        if (empty($iconContent)) {
            return $svgOutput;
        }

        // Determine mime type and convert icon payload to Base64
        $extension = File::extension($iconPath);
        $mimeType  = match ($extension) {
            'png'   => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp'  => 'image/webp',
            default => 'image/svg+xml',
        };

        $base64Href = sprintf('data:%s;base64,%s', $mimeType, base64_encode($iconContent));

        // Calculate dimensions for center badge overlay
        $totalSize = 450; // SVG viewBox dimension
        if (preg_match('/viewBox="0 0 (\d+) (\d+)"/', $svgOutput, $matches)) {
            $totalSize = (int) $matches[1];
        }

        $badgeSize   = (int) round($totalSize * 0.22);
        $iconSize    = (int) round($totalSize * 0.20);
        $badgeOffset = (int) round(($totalSize - $badgeSize) / 2);
        $iconOffset  = (int) round(($totalSize - $iconSize) / 2);

        // Render background badge only if transparency is disabled
        $rectElement = '';
        if (!$this->transparent) {
            $rectElement = sprintf(
                '<rect x="%d" y="%d" width="%d" height="%d" fill="#FFFFFF" rx="6" ry="6"/>',
                $badgeOffset,
                $badgeOffset,
                $badgeSize,
                $badgeSize
            );
        }

        $overlaySvg = sprintf(
            '<g>%s<image x="%d" y="%d" width="%d" height="%d" href="%s"/></g>',
            $rectElement,
            $iconOffset,
            $iconOffset,
            $iconSize,
            $iconSize,
            $base64Href
        );

        return str_replace('</svg>', $overlaySvg . '</svg>', $svgOutput);
    }

    /**
     * Internal handler to convert SVG output into raster graphics (PNG/JPG).
     * 
     * @param 'png'|'jpg' $format
     * @param bool $base64
     * @return string|null
     */
    protected function convertFormat(string $format, bool $base64 = false): ?string
    {
        $svgData = $this->getData();

        if (empty($svgData)) {
            return null;
        }

        $binaryData = null;

        try {
            // Load SVG string
            $image = SVG::fromString($svgData);

            /** @var \GdImage|resource $gdImage */
            $gdImage = $image->toRasterImage($this->scale * 33, $this->scale * 33);
            
            ob_start();
            if ($format === 'png') {
                imagepng($gdImage);
            } else {
                imagejpeg($gdImage, null, 95);
            }
            $binaryData = ob_get_clean();

            unset($gdImage);
        } catch (\Throwable $e) {
            Manager::silentError($e);
        }

        if (empty($binaryData)) {
            return null;
        }

        if ($base64) {
            $mime = ($format === 'jpg' || $format === 'jpeg') ? 'jpeg' : 'png';
            return "data:image/{$mime};base64," . base64_encode($binaryData);
        }

        return $binaryData;
    }
    
}