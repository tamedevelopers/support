<?php

namespace Tamedevelopers\Support;

use HTMLPurifier;
use HTMLPurifier_Config;
use Tamedevelopers\Support\Str;

class Purify
{
    private static $purifierString;
    
    /**
     * Init plain string purifier
     */
    private static function initString()
    {
        if (!self::$purifierString) {
            $config = HTMLPurifier_Config::createDefault();

            // Strip all HTML safely including scripts
            $config->set('HTML.Allowed', '');
            $config->set('HTML.Trusted', false);

            self::$purifierString = new HTMLPurifier($config);
        }
    }

    /**
     * purifier
     *
     * @param array $settings
     * @return \HTMLPurifier
     */
    protected static function purifier($settings = [])
    {
        $config = \HTMLPurifier_Config::createDefault();

        // Preserve formatting as-is
        $config->set('Core.NormalizeNewlines', false);
        $config->set('HTML.Trusted', true);
        $config->set('Attr.EnableID', true);
        $config->set('CSS.AllowTricky', true);
        $config->set('Attr.AllowedFrameTargets', ['_blank','_self','_parent','_top']);
        $config->set('HTML.AllowedAttributes', null); 

        // Required when extending HTML5 support
        $config->set('HTML.DefinitionID', 'custom-html5-definitions'); 
        $config->set('HTML.DefinitionRev', 1); // bump this if you change definitions

        // Merge custom overrides
        foreach ($settings as $key => $val) {
            $config->set($key, $val);
        }

        // ---- Extend HTML5 tags support ----
        if ($def = $config->maybeGetRawHTMLDefinition()) {
            // Structural / semantic tags
            $def->addElement('section', 'Block', 'Flow', 'Common');
            $def->addElement('article', 'Block', 'Flow', 'Common');
            $def->addElement('aside', 'Block', 'Flow', 'Common');
            $def->addElement('header', 'Block', 'Flow', 'Common');
            $def->addElement('footer', 'Block', 'Flow', 'Common');
            $def->addElement('main',    'Block', 'Flow', 'Common');
            $def->addElement('figure',  'Block', 'Flow', 'Common');
            $def->addElement('figcaption', 'Inline', 'Flow', 'Common');

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
     * Preserve structural newlines
     *
     * @param  string $content
     * @param  bool $collapse
     * @return string
     */
    protected static function preserveNewLine($content, $collapse = false)
    {
        $text = preg_replace('/<\s*br\s*\/?>/i', "\n", $content);
        $text = preg_replace('/<\/p\s*>/i', "\n\n", $text);
        $text = preg_replace('/<\/div\s*>/i', "\n\n", $text);
        $text = preg_replace('/<\/h[1-6]\s*>/i', "\n\n", $text);

        // Collapse whitespace
        if($collapse){
            $text = preg_replace('/\s+/u', ' ', $text);
        }

        return $text;
    }
    
    /**
     * cleanUrlLink
     *
     * @param  string $url
     * @return string
     */
    protected static function cleanUrlLink($url)
    {
        // Clean URL: decode %xx + HTML entities
        $url = rawurldecode($url ?: '');

        return html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Convert HTML content to readable string
     *
     * @param string $content
     * @param bool $allowUrl
     * @return string
     */
    public static function readable(string $content, bool $allowUrl = true): string
    {
        $text = $content;
        $text = self::preserveNewLine($text, true);

        // Handle all tags with link-like attributes (href, src, data-src, poster, etc.)
        $text = preg_replace_callback(
            '/<(a|img|iframe|video|audio|source|embed|track|script)[^>]+?(?:href|src|data-src|poster)=["\']([^"\']+)["\'][^>]*>(?:([\s\S]*?)<\/\1>)?/i',
            function ($matches) use($allowUrl) {
                if($allowUrl){
                    $tag   = strtolower($matches[1]);
                    $url   = self::cleanUrlLink($matches[2]);
                    $alt   = trim($matches[3] ?? '');   // alt attr if exists
                    $label = trim(strip_tags($matches[4] ?? '')); // inner text if exists

                    switch ($tag) {
                        case 'a':
                            // Prefer label, otherwise fall back to domain
                            return !empty($label) ? "[$label]" : "[link]";
                        case 'img':
                            return !empty($alt) ? "[$alt]" : "[image]";
                        case 'iframe':
                        case 'video':
                        case 'audio':
                        case 'source':
                        case 'embed':
                        case 'track':
                        case 'script':
                            // Prefer url if any
                            if (!empty($url)) {
                                return "[$url]";
                            }
                            return "[$tag]";
                        default:
                            return "[$tag]";
                    }
                } else{
                    return  "";
                }
            },
            $text
        );

        // Remove all other HTML tags
        $text = strip_tags($text);

        // Decode HTML entities (&amp; → & etc.)
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return html_entity_decode(
            trim($text), 
            ENT_QUOTES | ENT_HTML5, 'UTF-8'
        );
    }

    /**
     * Purify HTML for CMS/blog posts
     */
    public static function html(string $content): string
    {
        // Allow almost everything for CMS (iframe, video, embeds)
        $settings = [
            'HTML.SafeIframe' => true,
            'URI.SafeIframeRegexp' => '%^(https?:)?//%', // allow external iframes
            'HTML.SafeObject' => true,
            'Output.FlashCompat' => true,
        ];
        return self::purifier($settings)->purify($content);
    }

    /**
     * Purify for developer usage (keep all content including JS/style)
     */
    public static function dev(string $content): string
    {
        // Allow code/pre/dev tags but still sanitize dangerous stuff
        $settings = [
            'HTML.SafeIframe' => true,
            'URI.SafeIframeRegexp' => '%^(https?:)?//%',
            'HTML.SafeObject' => true,
            'Output.FlashCompat' => true,
            'HTML.AllowedElements' => null, // don't restrict, allow code-related tags too
        ];
        
        return self::purifier($settings)->purify($content);
    }

    /**
     * Purify for plain string (strip all HTML, scripts, unsafe content)
     */
    public static function string(string $content): string
    {
        self::initString();
        $clean = self::$purifierString->purify($content);
        return Str::trim($clean);
    }

    /**
     * Unsafe Purify HTML for CMS/blog posts
     */
    public static function raw(string $content): string
    {
        return $content;
    }

}