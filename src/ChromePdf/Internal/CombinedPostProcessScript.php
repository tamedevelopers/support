<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\ChromePdf\Internal;

/**
 * Single evaluate() payload: font readiness, optional theme/font CSS injection, optional settle, optional CMP strip.
 */
final class CombinedPostProcessScript
{
    /**
     * @param string|null $injectionCss Theme + auto font CSS (null or empty skips the style injection block)
     */
    public static function asExpression(
        bool $includeStability,
        bool $includeCookies,
        int $stabilityBudgetMs,
        int $fontRaceMs,
        ?string $injectionCss
    ): string {
        $max = max(400, min(20000, $stabilityBudgetMs));
        $race = max(50, min(30000, $fontRaceMs));

        $cssLiteral = '';
        if ($injectionCss !== null && $injectionCss !== '') {
            try {
                $encoded = json_encode($injectionCss, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                $encoded = '""';
            }
            $cssLiteral = <<<JS

                var __pdfCss = {$encoded};
                if (__pdfCss && __pdfCss.length) {
                    try {
                        var __st = document.createElement('style');
                        __st.setAttribute('type', 'text/css');
                        __st.setAttribute('data-support-chrome-pdf', '1');
                        __st.appendChild(document.createTextNode(__pdfCss));
                        var __head = document.head || document.getElementsByTagName('head')[0] || document.documentElement;
                        __head.appendChild(__st);
                    } catch (__eCss) {}
                }
            JS;
        }

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
            {$cssLiteral}
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
}
