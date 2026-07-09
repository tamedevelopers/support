<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\ChromePdf\Internal;

/**
 * Waits briefly for common ecommerce price nodes to appear after client-side hydration.
 */
final class PriceHydrationWaitScript
{
    public static function asExpression(int $maxWaitMs = 1500): string
    {
        $maxWaitMs = max(500, min(3000, $maxWaitMs));

        return '(async function () {
            const selectors = [
                "[data-pl=\\"product-price\\"]",
                ".product-price-value",
                ".price--current",
                ".price-current",
                "[itemprop=\\"price\\"]",
                ".x-price-primary",
                ".ux-textspans--PRICE",
                ".notranslate.price",
                "span[class*=\\"price\\"]",
            ];
            const deadline = Date.now() + ' . $maxWaitMs . ';
            while (Date.now() < deadline) {
                for (let i = 0; i < selectors.length; i++) {
                    const el = document.querySelector(selectors[i]);
                    const text = el && el.textContent ? el.textContent.trim() : "";
                    if (text && /[0-9]/.test(text)) {
                        return true;
                    }
                }
                const html = document.documentElement ? document.documentElement.innerHTML : "";
                if (/"(?:actMinPrice|minActivityAmount|skuAmount|priceAmount)"\\s*:\\s*\\{[^}]*"value"\\s*:\\s*[0-9]/.test(html)) {
                    return true;
                }
                await new Promise(function (r) { setTimeout(r, 150); });
            }
            return false;
        })();';
    }
}
