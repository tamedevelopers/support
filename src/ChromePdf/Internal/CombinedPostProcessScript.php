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
     */
    public static function asExpression(
        bool $includeStability,
        bool $includeCookies,
        int $stabilityBudgetMs,
        int $fontRaceMs,
        string $themeCss,
        array $fontFaceMap
    ): string {
        $max = max(400, min(20000, $stabilityBudgetMs));
        $race = max(50, min(30000, $fontRaceMs));

        $payload = [
            'theme' => $themeCss,
            'fonts' => $fontFaceMap,
        ];
        $payloadExpr = self::encodePayloadForEvaluate($payload);

        $stabilityAwait = '';
        if ($includeStability) {
            $stabilityAwait = 'await ' . PageStabilityScript::asSettleExpression($max) . ";\n";
        }

        $cookieBlock = '';
        if ($includeCookies) {
            $cookieBlock = CookiePopupRemovalScript::asExpression() . ";\n";
        }

        return <<<JS
            (async function () {
                var __payload = {$payloadExpr};
                var __theme = (typeof __payload.theme === 'string') ? __payload.theme : '';
                var __fonts = __payload.fonts || {};
                function __appendStyle(css, attr) {
                    if (!css || !css.length) return;
                    try {
                        var el = document.createElement('style');
                        el.setAttribute('type', 'text/css');
                        el.setAttribute('data-support-chrome-pdf', attr || '1');
                        el.appendChild(document.createTextNode(css));
                        var head = document.head || document.getElementsByTagName('head')[0] || document.documentElement;
                        head.appendChild(el);
                    } catch (e) {}
                }
                if (__theme.length) {
                    __appendStyle(__theme, 'theme');
                }
                function __sampleText() {
                    try {
                        var b = document.body;
                        if (b && b.innerText) return b.innerText.substring(0, 50000);
                        var r = document.documentElement;
                        return (r && r.innerText) ? r.innerText.substring(0, 50000) : '';
                    } catch (e) { return ''; }
                }
                function __hasCJK(s) { return /[\u3040-\u30ff\u3400-\u4dbf\u4e00-\u9fff\uf900-\ufaff]/.test(s); }
                function __hasArabic(s) { return /[\u0600-\u06ff\u0750-\u077f\u08a0-\u08ff\ufb50-\ufdff\ufe70-\ufeff]/.test(s); }
                function __hasCyrillic(s) { return /[\u0400-\u04ff]/.test(s); }
                var __t = __sampleText();
                var __order = ['cjk', 'arabic', 'cyrillic'];
                var __checks = { cjk: __hasCJK, arabic: __hasArabic, cyrillic: __hasCyrillic };
                var __faceParts = [];
                var __fam = [];
                for (var __i = 0; __i < __order.length; __i++) {
                    var __k = __order[__i];
                    var __css = __fonts[__k];
                    if (!__css || !__css.length) continue;
                    if (!__checks[__k] || !__checks[__k](__t)) continue;
                    __faceParts.push(__css);
                    __fam.push('"SupportPdf_' + __k + '"');
                }
                if (__faceParts.length) {
                    var __bundle = __faceParts.join('\\n') + '\\nhtml, body { font-family: ' + __fam.join(', ') + ', "PingFang SC", "Microsoft YaHei", sans-serif !important; }';
                    __appendStyle(__bundle, 'fonts');
                }
                var raceMs = {$race};
                try {
                    if (document.fonts && document.fonts.ready) {
                        await Promise.race([
                            document.fonts.ready,
                            new Promise(function (r) { setTimeout(r, raceMs); })
                        ]);
                    }
                } catch (__eFont) {}
                await new Promise(function (r) {
                    requestAnimationFrame(function () {
                        requestAnimationFrame(function () { r(); });
                    });
                });
                await new Promise(function (r) { setTimeout(r, 50); });
                {$stabilityAwait}{$cookieBlock}
                return true;
            })()
            JS;
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
        } catch (\JsonException) {
            $inner = '{"theme":"","fonts":{}}';
        }

        try {
            return 'JSON.parse(' . json_encode($inner, $flags | JSON_THROW_ON_ERROR) . ')';
        } catch (\JsonException) {
            return 'JSON.parse(' . json_encode('{"theme":"","fonts":{}}', $flags | JSON_THROW_ON_ERROR) . ')';
        }
    }
}
