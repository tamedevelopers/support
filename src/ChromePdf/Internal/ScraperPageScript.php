<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\ChromePdf\Internal;

/**
 * One evaluate() for scraper fetches: navigation settle, Cloudflare, price hydration, return HTML.
 */
final class ScraperPageScript
{
    public static function asExpression(int $cloudflareMs = 2000, int $priceMs = 800): string
    {
        $cloudflareMs = max(500, min(3000, $cloudflareMs));
        $priceMs = max(300, min(1500, $priceMs));
        $navMs = 1200;

        return '(async function () {
            const navDeadline = Date.now() + ' . $navMs . ';
            while ((!document.body || document.readyState === "loading") && Date.now() < navDeadline) {
                await new Promise(function (r) { setTimeout(r, 50); });
            }

            const cfNeedles = ["just a moment", "performing security verification", "checking your browser", "verify you are human", "attention required"];
            const cfHtml = ["cf-challenge", "/cdn-cgi/challenge-platform", "challenges.cloudflare.com"];
            const cfDeadline = Date.now() + ' . $cloudflareMs . ';
            while (Date.now() < cfDeadline) {
                const title = (document.title || "").toLowerCase();
                const text = (document.body && document.body.innerText ? document.body.innerText : "").toLowerCase();
                const html = (document.documentElement && document.documentElement.outerHTML ? document.documentElement.outerHTML : "").toLowerCase();
                let blocked = cfNeedles.some(function (n) { return title.indexOf(n) !== -1 || text.indexOf(n) !== -1; });
                if (!blocked) {
                    blocked = cfHtml.some(function (n) { return html.indexOf(n) !== -1; });
                }
                if (!blocked) { break; }
                await new Promise(function (r) { setTimeout(r, 150); });
            }

            await new Promise(function (r) { setTimeout(r, 60); });

            const priceSelectors = [
                "[data-pl=\\"product-price\\"]", ".product-price-value", ".price--current",
                "[itemprop=\\"price\\"]", ".x-price-primary", ".ux-textspans--PRICE", ".notranslate.price"
            ];
            const priceDeadline = Date.now() + ' . $priceMs . ';
            let priceFound = false;
            while (Date.now() < priceDeadline && !priceFound) {
                for (let i = 0; i < priceSelectors.length; i++) {
                    const el = document.querySelector(priceSelectors[i]);
                    const t = el && el.textContent ? el.textContent.trim() : "";
                    if (t && /[0-9]/.test(t)) { priceFound = true; break; }
                }
                if (!priceFound) {
                    const src = document.documentElement ? document.documentElement.innerHTML : "";
                    if (/"(?:actMinPrice|minActivityAmount|skuAmount|priceAmount)"\\s*:\\s*\\{[^}]*"value"\\s*:\\s*[0-9]/.test(src)) {
                        priceFound = true;
                    }
                }
                if (!priceFound) {
                    await new Promise(function (r) { setTimeout(r, 100); });
                }
            }

            if (document.documentElement && document.documentElement.outerHTML) {
                return document.documentElement.outerHTML;
            }
            return document.body ? document.body.innerHTML : "";
        })();';
    }
}
