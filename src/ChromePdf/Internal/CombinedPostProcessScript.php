<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\ChromePdf\Internal;

/**
 * Single evaluate(): theme + smart font injection, font readiness, optional settle, optional CMP strip.
 */
final class CombinedPostProcessScript
{
    /**
     * @param array<string, string> $fontFaceMap Keyed by {@code cjk}, {@code arabic}, {@code cyrillic}
     * @param bool $leanStability forwarded to {@see PageStabilityScript::asSettleExpression()} when stability runs
     * @param int $paintSettleMs extra delay after font / rAF paint (0–80); lower for {@code prioritizeSpeed}
     * @param bool $waitForImages when true, polls {@code document.images} for completeness (local file/html sources)
     * @param int $imageWaitMs cap for image-readiness poll
     */
    public static function asExpression(
        bool $includeStability,
        bool $includeCookies,
        int $stabilityBudgetMs,
        int $fontRaceMs,
        string $themeCss,
        array $fontFaceMap,
        bool $leanStability = false,
        int $paintSettleMs = 50,
        bool $waitForImages = false,
        int $imageWaitMs = 2000
    ): string {
        $max = max(400, min(20000, $stabilityBudgetMs));
        $race = max(50, min(30000, $fontRaceMs));
        $paint = max(0, min(80, $paintSettleMs));
        $imgCap = max(200, min(8000, $imageWaitMs));

        $payload = [
            'theme' => $themeCss,
            'fonts' => $fontFaceMap,
        ];
        $payloadExpr = self::encodePayloadForEvaluate($payload);

        $js = "(async function () {";
        $js .= "var __payload = {$payloadExpr};";
        $js .= "var __theme = __payload.theme || '';";
        $js .= "var __fonts = __payload.fonts || {};";

        $js .= "function appendStyle(css, id) {"
            . "if(!css) return;"
            . "var s = document.createElement('style');"
            . "s.id = id;"
            . "s.textContent = css;"
            . "document.head.appendChild(s);"
            . "}";

        $js .= "appendStyle(__theme, 'pdf-theme');";

        $js .= "var txt = (document.body && document.body.innerText) ? document.body.innerText.substring(0, 10000) : '';";
        $js .= "if(/[\\u3040-\\u9fff]/.test(txt) && __fonts.cjk) appendStyle(__fonts.cjk, 'pdf-font-cjk');";
        $js .= "if(/[\\u0600-\\u06ff]/.test(txt) && __fonts.arabic) appendStyle(__fonts.arabic, 'pdf-font-arabic');";

        if ($waitForImages) {
            $js .= "var __imgEnd = Date.now() + {$imgCap};"
                . "function __imgsReady() {"
                .     "var imgs = document.images;"
                .     "for (var i = 0; i < imgs.length; i++) {"
                .         "if (!imgs[i].complete) return false;"
                .     "}"
                .     "return true;"
                . "}"
                . "while (!__imgsReady() && Date.now() < __imgEnd) {"
                .     "await new Promise(function (r) { setTimeout(r, 60); });"
                . "}";
        }

        if ($includeStability) {
            $js .= "await new Promise(function (r) { setTimeout(r, {$paint}); });";
        }

        $js .= "return true; })();";

        return $js;
    }

    /**
     * Returns a JS expression that evaluates to the payload object (safe inside {@code evaluate()}).
     *
     * @param array{theme: string, fonts: array<string, string>} $payload
     */
    public static function encodePayloadForEvaluate(array $payload): string
    {
        $flags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP;
        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
        }

        try {
            $inner = json_encode($payload, $flags | JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $inner = '{"theme":"","fonts":{}}';
        }

        try {
            return 'JSON.parse(' . json_encode($inner, $flags | JSON_THROW_ON_ERROR) . ')';
        } catch (\JsonException $e) {
            return 'JSON.parse(' . json_encode('{"theme":"","fonts":{}}', $flags | JSON_THROW_ON_ERROR) . ')';
        }
    }
}