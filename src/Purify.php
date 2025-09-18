<?php

namespace Tamedevelopers\Support;

use HTMLPurifier;
use HTMLPurifier_Config;
use Tamedevelopers\Support\Str;

class Purify
{
    private static $purifierHtml;
    private static $purifierDev;
    private static $purifierString;

    /**
     * Init CMS-safe purifier
     */
    private static function initHtml()
    {
        if (!self::$purifierHtml) {
            $config = HTMLPurifier_Config::createDefault();

            // Core settings
            $config->set('HTML.SafeIframe', true);
            $config->set('AutoFormat.Linkify', true);
            $config->set('AutoFormat.AutoParagraph', true);
            $config->set('Core.EscapeInvalidTags', false);

            // Set unique ID for extended definition (required for maybeGetRawHTMLDefinition)
            $config->set('HTML.DefinitionID', 'cms-html5-purifier'); // unique name
            $config->set('HTML.DefinitionRev', 1); // revision number

            // Allowed tags and attributes for CMS/blog content
            $config->set('HTML.Allowed', implode(',', [
                // Links & references
                'a[href|title|target]',       // links with optional title and target
                'abbr[title]',                // abbreviations
                'acronym[title]',             // acronyms
                'b', 'strong',                // bold text
                'i', 'em',                    // italic text
                'u',                          // underline
                'strike',                     // strikethrough
                'sub', 'sup',                 // subscripts/superscripts
                'mark',                       // highlighted text
                'p', 'br', 'hr',
                'h1','h2','h3','h4','h5','h6',
                'blockquote[cite]',
                'code', 'pre',
                'ul','ol','li','dl','dt','dd',
                'table','thead','tbody','tfoot','tr','th','td',
                'img[src|alt|title|width|height]',
                'audio[src|controls|width|height|preload]',
                'video[src|controls|width|height|preload|poster]',
                'header','footer','main','section','article','aside','figure','figcaption','nav',
                'div[style|class|id]',
                'span[style|class|id]',
                'iframe[src|width|height|frameborder|allow|allowfullscreen]',
            ]));

            // Extend HTML5 support for semantic tags and media
            $def = $config->maybeGetRawHTMLDefinition();
            if ($def) {
                // Semantic blocks
                $def->addElement('header', 'Block', 'Flow', 'Common');
                $def->addElement('footer', 'Block', 'Flow', 'Common');
                $def->addElement('main', 'Block', 'Flow', 'Common');
                $def->addElement('section', 'Block', 'Flow', 'Common');
                $def->addElement('article', 'Block', 'Flow', 'Common');
                $def->addElement('aside', 'Block', 'Flow', 'Common');
                $def->addElement('figure', 'Block', 'Optional: (figcaption, Flow)', 'Common');
                $def->addElement('figcaption', 'Inline', 'Flow', 'Common');
                $def->addElement('nav', 'Block', 'Flow', 'Common');

                // Multimedia
                $def->addElement('audio', 'Block', 'Optional: Flow', 'Common', ['src' => 'URI', 'controls' => 'Bool', 'width' => 'Length', 'height' => 'Length', 'preload' => 'Enum#auto,metadata,none']);
                $def->addElement('video', 'Block', 'Optional: Flow', 'Common', ['src' => 'URI', 'controls' => 'Bool', 'width' => 'Length', 'height' => 'Length', 'poster' => 'URI', 'preload' => 'Enum#auto,metadata,none']);
            }

            self::$purifierHtml = new HTMLPurifier($config);
        }
    }

    /**
     * Init Dev-safe purifier (allow all tags, attributes, JS, style)
     */
    private static function initDev()
    {
        if (!self::$purifierDev) {
            $config = HTMLPurifier_Config::createDefault();

            // Allow all HTML, including script/style for dev usage
            $config->set('HTML.Allowed', null);
            $config->set('HTML.SafeEmbed', true);
            $config->set('HTML.SafeObject', true);
            $config->set('HTML.SafeIframe', true);
            $config->set('CSS.AllowTricky', true);
            $config->set('HTML.Trusted', true); // keep script/style for dev

            self::$purifierDev = new HTMLPurifier($config);
        }
    }

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
     * Purify HTML for CMS/blog posts
     */
    public static function html(string $content): string
    {
        self::initHtml();
        return self::$purifierHtml->purify($content);
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
     * Purify for developer usage (keep all content including JS/style)
     */
    public static function dev(string $content): string
    {
        self::initDev();
        return self::$purifierDev->purify($content);
    }
}
