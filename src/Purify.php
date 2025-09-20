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

            // Unique definition ID/revision
            $config->set('HTML.DefinitionID', 'cms-html5-purifier');
            $config->set('HTML.DefinitionRev', 2);

            // Base allowed tags/attributes (only those HTMLPurifier natively supports)
            $config->set('HTML.Allowed', implode(',', [
                'a[href|title|target]',
                'abbr[title]', 'acronym[title]',
                'b', 'strong', 'i', 'em', 'u', 'strike',
                'sub', 'sup',
                'p', 'br', 'hr',
                'h1','h2','h3','h4','h5','h6',
                'blockquote[cite]',
                'code', 'pre',
                'ul','ol','li','dl','dt','dd',
                'table','thead','tbody','tfoot','tr','th','td',
                'img[src|alt|title|width|height]',
                'div[style|class|id]',
                'span[style|class|id]',
            ]));

            // Extend HTML5 support
            if ($def = $config->maybeGetRawHTMLDefinition()) {
                // Semantic HTML5
                $def->addElement('mark', 'Inline', 'Inline', 'Common');
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
                $def->addElement('audio', 'Block', 'Optional: Flow', 'Common', [
                    'src'     => 'URI',
                    'controls'=> 'Bool',
                    'width'   => 'Length',
                    'height'  => 'Length',
                    'preload' => 'Enum#auto,metadata,none'
                ]);
                $def->addElement('video', 'Block', 'Optional: Flow', 'Common', [
                    'src'     => 'URI',
                    'controls'=> 'Bool',
                    'width'   => 'Length',
                    'height'  => 'Length',
                    'poster'  => 'URI',
                    'preload' => 'Enum#auto,metadata,none'
                ]);
                $def->addElement('source', 'Block', 'Flow', 'Common', [
                    'src'  => 'URI',
                    'type' => 'Text'
                ]);

                // iframe with extended attributes
                $def->addElement('iframe', 'Block', 'Flow', 'Common', [
                    'src'             => 'URI',
                    'width'           => 'Length',
                    'height'          => 'Length',
                    'frameborder'     => 'Text',
                    'allow'           => 'Text',
                    'allowfullscreen' => 'Bool'
                ]);
            }

            self::$purifierHtml = new \HTMLPurifier($config);
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
