<?php

namespace Tamedevelopers\Support;

use HTMLPurifier;
use HTMLPurifier_Config;
use Tamedevelopers\Support\Str;

class Purify
{
    /**
     * Singleton instances for default purifiers.
     */
    private static ?HTMLPurifier $stringPurifier = null;
    private static ?HTMLPurifier $htmlPurifier = null;
    private static ?HTMLPurifier $devPurifier = null;

    /**
     * Build standard HTMLPurifier instance with custom HTML5 definitions.
     *
     * @param array $settings
     * @return HTMLPurifier
     */
    protected static function purifier(array $settings = []): HTMLPurifier
    {
        $config = HTMLPurifier_Config::createDefault();

        // Preserve formatting and security defaults
        $config->set('Attr.AllowedFrameTargets', ['_blank', '_self', '_parent', '_top']);

        // Custom default configuration
        $settings = array_merge([
            'Attr.EnableID'         => true,
            'CSS.AllowTricky'       => true,
            'Core.NormalizeNewlines'=> false,
            'HTML.DefinitionID'     => 'custom-html5-definitions',
            'HTML.DefinitionRev'    => 2,
        ], $settings);
        
        // Apply custom overrides
        foreach ($settings as $key => $val) {
            $config->set($key, $val);
        }

        // Extend HTML5 element support
        if ($def = $config->maybeGetRawHTMLDefinition()) {
            // Structural / semantic tags
            $def->addElement('section', 'Block', 'Flow', 'Common');
            $def->addElement('article', 'Block', 'Flow', 'Common');
            $def->addElement('aside', 'Block', 'Flow', 'Common');
            $def->addElement('header', 'Block', 'Flow', 'Common');
            $def->addElement('footer', 'Block', 'Flow', 'Common');
            $def->addElement('main', 'Block', 'Flow', 'Common');
            $def->addElement('figure', 'Block', 'Flow', 'Common');
            $def->addElement('figcaption', 'Inline', 'Flow', 'Common');
            $def->addElement('pre', 'Block', 'Flow', 'Common');
            $def->addElement('code', 'Inline', 'Flow', 'Common');

            // Media tags
            $def->addElement('video', 'Block', 'Flow', 'Common', [
                'src'      => 'URI',
                'type'     => 'Text',
                'width'    => 'Length',
                'height'   => 'Length',
                'poster'   => 'URI',
                'preload'  => 'Enum#auto,metadata,none',
                'controls' => 'Bool',
                'autoplay' => 'Bool',
                'loop'     => 'Bool',
                'muted'    => 'Bool',
            ]);

            $def->addElement('audio', 'Block', 'Flow', 'Common', [
                'src'      => 'URI',
                'preload'  => 'Enum#auto,metadata,none',
                'controls' => 'Bool',
                'autoplay' => 'Bool',
                'loop'     => 'Bool',
                'muted'    => 'Bool',
            ]);

            $def->addElement('source', 'Block', 'Empty', 'Common', [
                'src'  => 'URI',
                'type' => 'Text',
            ]);

            // Time tag
            $def->addElement('time', 'Inline', 'Inline', 'Common', [
                'datetime' => 'Text',
            ]);
        }

        return new HTMLPurifier($config);
    }

    /**
     * Preserve structural newlines prior to stripping tags.
     *
     * @param string $content
     * @param bool $collapse
     * @return string
     */
    protected static function preserveNewLine(string $content, bool $collapse = false): string
    {
        $text = preg_replace('/<\s*br\s*\/?>/i', "\n", (string) $content);
        $text = preg_replace('/<\/(p|div|h[1-6]|li)\s*>/i', "\n\n", $text);

        // Collapse whitespace
        if ($collapse) {
            // Collapse horizontal whitespace (spaces/tabs) without removing newlines
            $text = preg_replace('/[^\S\r\n]+/u', ' ', $text);

            // Collapse 3 or more consecutive newlines into 2
            $text = preg_replace('/\n{3,}/', "\n\n", $text);
        }

        return $text;
    }

    /**
     * Clean and decode URL strings.
     *
     * @param string $url
     * @return string
     */
    protected static function cleanUrlLink(string $url): string
    {
        return html_entity_decode(rawurldecode($url), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Convert HTML content into a human-readable plain text format.
     *
     * @param string $content
     * @param bool $allowUrl
     * @return string
     */
    public static function readable(string $content, bool $allowUrl = true): string
    {
        $content = self::preserveNewLine($content, true);

        // Process elements with link/src attributes
        $text = preg_replace_callback(
            '/<(a|img|iframe|video|audio|source|embed|track|script)\b[^>]*?(?:href|src|data-src|poster)=["\']([^"\']+)["\'][^>]*>(?:([\s\S]*?)<\/\1>)?/i',
            function ($matches) use ($allowUrl) {
                if (!$allowUrl) {
                    return '';
                }

                $tag   = strtolower($matches[1]);
                $url   = self::cleanUrlLink($matches[2]);
                $inner = trim($matches[3] ?? '');

                switch ($tag) {
                    case 'a':
                        $label = trim(strip_tags($inner));
                        return !empty($label) ? "[$label]" : '[link]';

                    case 'img':
                        // Check full tag for alt attribute
                        if (preg_match('/alt=["\']([^"\']+)["\']/i', $matches[0], $altMatch)) {
                            $alt = trim($altMatch[1]);
                            return !empty($alt) ? "[$alt]" : '[image]';
                        }
                        return '[image]';

                    case 'iframe':
                    case 'video':
                    case 'audio':
                    case 'source':
                    case 'embed':
                    case 'track':
                    case 'script':
                        return !empty($url) ? "[$url]" : "[$tag]";

                    default:
                        return "[$tag]";
                }
            },
            $content
        );

        // Strip remaining HTML tags
        $text = strip_tags($text);

        // Decode HTML entities
        return trim(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /**
     * Purify HTML for standard web and CMS content.
     *
     * @param string $content
     * @return string
     */
    public static function html(string $content): string
    {
        if (!self::$htmlPurifier) {
            self::$htmlPurifier = self::purifier([
                'HTML.SafeIframe'       => true,
                'URI.SafeIframeRegexp'  => '%^(https?:)?//%', // allow external iframes
                'HTML.SafeObject'       => true,
                'Output.FlashCompat'    => true,
            ]);
        }

        return self::$htmlPurifier->purify($content);
    }

    /**
     * Purify for developer usage (keep all content including JS/style)
     */
    public static function dev(string $content): string
    {
        if (!self::$devPurifier) {
            self::$devPurifier = self::purifier([
                'HTML.SafeIframe'       => true,
                'URI.SafeIframeRegexp'  => '%^(https?:)?//%', // allow external iframes
                'HTML.SafeObject'       => true,
                'Output.FlashCompat'    => true,
                'HTML.SafeEmbed'        => true,
                'HTML.Trusted'          => true,
                'HTML.DefinitionRev'    => 3,
                'HTML.DefinitionID'     => 'dev-cms-definitions',
            ]);
        }

        return self::$devPurifier->purify($content);
    }

    /**
     * Purify string content by stripping all HTML tags safely.
     *
     * @param string $content
     * @return string
     */
    public static function string(string $content): string
    {
        if (!self::$stringPurifier) {
            $config = HTMLPurifier_Config::createDefault();
            $config->set('HTML.Allowed', '');

            self::$stringPurifier = new HTMLPurifier($config);
        }

        $clean = self::$stringPurifier->purify($content);
        
        return Str::trim($clean);
    }

    /**
     * Return raw content without purification.
     *
     * @param string $content
     * @return string
     */
    public static function raw(string $content): string
    {
        return $content;
    }

}