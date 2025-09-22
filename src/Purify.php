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