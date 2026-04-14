<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\ChromePdf\Internal;

/**
 * Removes common cookie / GDPR consent overlays before print-to-PDF.
 * Flat {@code querySelectorAll} approach — no recursive tree walkers or shadow-root traversal.
 */
final class CookiePopupRemovalScript
{
    public static function asExpression(): string
    {
        return <<<'JS'
            (function () {
                try {
                    var knownSelectors = [
                        '#onetrust-banner-sdk','#onetrust-consent-sdk','#onetrust-pc-sdk','.onetrust-pc-dark-filter',
                        '#CybotCookiebotDialog','#CybotCookiebotDialogBodyUnderlay','#CookiebotDialog',
                        '#qc-cmp2-ui','#qc-cmp2-container','.qc-cmp2-container',
                        '#sp-cc','#TrustArc','#truste-consent-track','.trustarc-banner',
                        '.cc-window','.cc-banner',
                        '#cookiescript_injected','#cookiescript_injected_wrapper',
                        '#cookie-law-info-bar','.cli-bar-container','#cliSettingsPopup',
                        '#tarteaucitronRoot','#joomla-cookie-banner','#cookiesdirective',
                        '#pn-cookie-banner','#hs-eu-cookie-confirmation',
                        '#usercentrics-root','#uc-banner-modal',
                        '[data-testid="cookie-banner"]',
                        'div[id*="cookie-banner"]','div[id*="cookie_banner"]','div[id*="cookieconsent"]',
                        'div[class*="cookie-banner"]','div[class*="cookie_banner"]',
                        'div[class*="CookieConsent"]','div[class*="cookie-consent"]',
                        'aside[id*="cookie"]','aside[class*="cookie"]',
                        'form[action*="consent.google"]',
                        '#didomi-host','.didomi-popup-container',
                        '#termly-code-snackbar-support','.termly-styles',
                        '#klaro','.klaro','#orejime-root','.orejime-Root',
                        '[data-cookiefirst-banner]','#cookiesealbanner',
                        '#cookie-information-template-wrapper','.cookiefirst-root',
                        '#fides-overlay','.fc-consent-root',
                        '#privacy-banner','#privacy-settings',
                        '#ncmp__tool','.ncmp__banner','#ncmpconsent',
                        '.evidon-banner','#evidon-prefdiag-overlay',
                        '#SourcepointPMM','.sp_message_container',
                        '#ez-cookie-dialog-wrapper','.ez-cookie-dialog-wrapper',
                        '#cookiebot','.cookiebot','[data-nosnippet][class*="cookie"]'
                    ];
                    knownSelectors.forEach(function (sel) {
                        try {
                            document.querySelectorAll(sel).forEach(function (n) { try { n.remove(); } catch (e) {} });
                        } catch (e2) {}
                    });
                } catch (eK) {}

                try {
                    var consentRe = /cookie|consent|gdpr|privacy|we use cookies|before you continue|accept all|reject all/i;
                    var vw = window.innerWidth;
                    var vh = window.innerHeight;
                    document.querySelectorAll('div,section,aside,dialog,form,c-wiz').forEach(function (el) {
                        try {
                            var st = window.getComputedStyle(el);
                            var pos = st.position;
                            if (pos !== 'fixed' && pos !== 'sticky') return;
                            var z = parseInt(st.zIndex, 10);
                            if (!(isFinite(z) && z >= 40)) return;
                            var r = el.getBoundingClientRect();
                            if (r.width < vw * 0.3 || r.height < 60) return;
                            var id = (el.id || '');
                            var cl = typeof el.className === 'string' ? el.className : '';
                            var txt = (el.innerText || '').slice(0, 400);
                            if (consentRe.test(id + ' ' + cl) || consentRe.test(txt)) {
                                el.remove();
                            }
                        } catch (eEl) {}
                    });
                } catch (eH) {}

                try {
                    document.documentElement.classList.remove('osano-cm-prevent-scroll', 'cm-overlay-open');
                    if (document.body) {
                        document.body.classList.remove('osano-cm-prevent-scroll', 'cm-overlay-open');
                        document.body.style.removeProperty('overflow');
                        document.body.style.removeProperty('position');
                    }
                    document.documentElement.style.removeProperty('overflow');
                } catch (eC) {}

                return true;
            })()
        JS;
    }
}
