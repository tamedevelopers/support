<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\ChromePdf\Internal;

/**
 * In-page script to remove common cookie / GDPR consent layers before print-to-PDF.
 * Handles shadow roots (Google c-wiz) and tries consent-button clicks when removal alone fails.
 */
final class CookiePopupRemovalScript
{
    /**
     * Expression for {@see \HeadlessChromium\Page::evaluate()}; returns true when finished.
     */
    public static function asExpression(): string
    {
        return <<<'JS'
            (function () {
                function safeQueryAll(sel, root) {
                    root = root || document;
                    try {
                        return Array.prototype.slice.call(root.querySelectorAll(sel));
                    } catch (e) {
                        return [];
                    }
                }
                function removeList(selectors) {
                    selectors.forEach(function (sel) {
                        safeQueryAll(sel).forEach(function (node) {
                            try {
                                node.remove();
                            } catch (e) {}
                        });
                    });
                }
                function walkElements(root, callback) {
                    if (!root) {
                        return;
                    }
                    if (root.nodeType === 1) {
                        callback(root);
                    }
                    var child = root.firstElementChild;
                    while (child) {
                        walkElements(child, callback);
                        child = child.nextElementSibling;
                    }
                    if (root.nodeType === 1 && root.shadowRoot) {
                        walkElements(root.shadowRoot, callback);
                    }
                }
                function innerTextSnippet(el, maxLen) {
                    maxLen = maxLen || 400;
                    try {
                        var t = (el.innerText || el.textContent || '').trim();
                        return t.length > maxLen ? t.slice(0, maxLen) : t;
                    } catch (e) {
                        return '';
                    }
                }
                function consentTextRegex() {
                    return /before you continue|we use cookies|cookies and data|privacy and terms|reject all|accept all/i;
                }
                function treeTextSample(el, maxLen, depth) {
                    maxLen = maxLen || 300;
                    depth = depth || 0;
                    if (depth > 30 || !el) {
                        return '';
                    }
                    try {
                        var parts = [];
                        if (el.nodeType === 1) {
                            var it = (el.innerText || '').trim();
                            if (it) {
                                parts.push(it.slice(0, maxLen));
                            }
                        }
                        var txt = (el.textContent || '').trim();
                        if (txt && parts.join('').length < maxLen) {
                            parts.push(txt.slice(0, maxLen));
                        }
                        return parts.join(' ').slice(0, maxLen);
                    } catch (e) {
                        return '';
                    }
                }
                function hasConsentInSubtree(el, depth) {
                    depth = depth || 0;
                    if (depth > 35 || !el || el.nodeType !== 1) {
                        return false;
                    }
                    var sample = treeTextSample(el, 500, depth);
                    if (consentTextRegex().test(sample)) {
                        return true;
                    }
                    var c = el.firstElementChild;
                    while (c) {
                        if (hasConsentInSubtree(c, depth + 1)) {
                            return true;
                        }
                        c = c.nextElementSibling;
                    }
                    if (el.shadowRoot) {
                        var s = el.shadowRoot.firstElementChild;
                        while (s) {
                            if (hasConsentInSubtree(s, depth + 1)) {
                                return true;
                            }
                            s = s.nextElementSibling;
                        }
                    }
                    return false;
                }
                function deepFindById(root, wantId, depth) {
                    depth = depth || 0;
                    if (depth > 45 || !root || root.nodeType !== 1) {
                        return null;
                    }
                    if (root.id === wantId) {
                        return root;
                    }
                    var ch = root.firstElementChild;
                    while (ch) {
                        var f = deepFindById(ch, wantId, depth + 1);
                        if (f) {
                            return f;
                        }
                        ch = ch.nextElementSibling;
                    }
                    if (root.shadowRoot) {
                        var sh = root.shadowRoot.firstElementChild;
                        while (sh) {
                            var f2 = deepFindById(sh, wantId, depth + 1);
                            if (f2) {
                                return f2;
                            }
                            sh = sh.nextElementSibling;
                        }
                    }
                    return null;
                }
                function tryClickConsentButtons() {
                    var ids = ['L2AGLb', 'W0wltc'];
                    for (var i = 0; i < ids.length; i++) {
                        var n = deepFindById(document.documentElement, ids[i], 0);
                        if (n && typeof n.click === 'function') {
                            try {
                                n.click();
                            } catch (e) {}
                        }
                    }
                }
                function removeAncestorsUntilLargePanel(node, maxHops) {
                    maxHops = maxHops || 12;
                    var p = node;
                    for (var i = 0; i < maxHops && p; i++) {
                        if (p.nodeName === 'BODY' || p.nodeName === 'HTML') {
                            break;
                        }
                        var r = p.getBoundingClientRect();
                        if (r.width >= window.innerWidth * 0.45 && r.height >= window.innerHeight * 0.35) {
                            try {
                                p.remove();
                            } catch (e) {}
                            return true;
                        }
                        p = p.parentElement;
                    }
                    return false;
                }
                function wantTextProbe(el) {
                    var t = el.nodeName;
                    if (t === 'DIALOG' || t === 'C-WIZ') {
                        return true;
                    }
                    if (t !== 'DIV' && t !== 'SECTION' && t !== 'ASIDE' && t !== 'FORM') {
                        return false;
                    }
                    var id = (el.id || '').toLowerCase();
                    var cl = typeof el.className === 'string' ? el.className.toLowerCase() : '';
                    var role = (el.getAttribute('role') || '').toLowerCase();
                    var aria = (el.getAttribute('aria-label') || '').toLowerCase();
                    var blob = id + ' ' + cl + ' ' + role + ' ' + aria;
                    if (/cookie|consent|gdpr|privacy|banner|modal|cmp|fc-|qs-|onetrust|osano|usercentrics|sp-cc|truste|didomi|termly|klaro|orejime|cookiefirst|fides|quantcast/i.test(blob)) {
                        return true;
                    }
                    if (role === 'dialog' || role === 'alertdialog') {
                        return true;
                    }
                    try {
                        var st = window.getComputedStyle(el);
                        var z = parseInt(st.zIndex, 10);
                        if ((st.position === 'fixed' || st.position === 'sticky') && isFinite(z) && z >= 50) {
                            var r = el.getBoundingClientRect();
                            if (r.width >= window.innerWidth * 0.42 && r.height >= 80 && r.height <= window.innerHeight * 0.95) {
                                return true;
                            }
                        }
                    } catch (e) {}
                    return false;
                }

                tryClickConsentButtons();

                var knownSelectors = [
                    '#onetrust-banner-sdk',
                    '#onetrust-consent-sdk',
                    '#onetrust-pc-sdk',
                    '.onetrust-pc-dark-filter',
                    '#CybotCookiebotDialog',
                    '#CybotCookiebotDialogBodyUnderlay',
                    '#CookiebotDialog',
                    '#cookiebot',
                    '.cookiebot',
                    '#qc-cmp2-ui',
                    '#qc-cmp2-container',
                    '.qc-cmp2-container',
                    '#sp-cc',
                    '#TrustArc',
                    '#truste-consent-track',
                    '.trustarc-banner',
                    '.cc-window',
                    '.cc-banner',
                    '#cookiescript_injected',
                    '#cookie-law-info-bar',
                    '.cli-bar-container',
                    '#cliSettingsPopup',
                    '#tarteaucitronRoot',
                    '#joomla-cookie-banner',
                    '#cookiesdirective',
                    '#pn-cookie-banner',
                    '#hs-eu-cookie-confirmation',
                    '#usercentrics-root',
                    '#uc-banner-modal',
                    '[data-testid="cookie-banner"]',
                    '[data-nosnippet][class*="cookie"]',
                    'div[id*="cookie-banner"]',
                    'div[id*="cookie_banner"]',
                    'div[id*="cookieconsent"]',
                    'div[class*="cookie-banner"]',
                    'div[class*="cookie_banner"]',
                    'div[class*="CookieConsent"]',
                    'div[class*="cookie-consent"]',
                    'aside[id*="cookie"]',
                    'aside[class*="cookie"]',
                    'form[action*="consent.google"]',
                    '#didomi-host',
                    '.didomi-popup-container',
                    '#termly-code-snackbar-support',
                    '.termly-styles',
                    '#klaro',
                    '.klaro',
                    '#orejime-root',
                    '.orejime-Root',
                    '[data-cookiefirst-banner]',
                    '#cookiesealbanner',
                    '#cookie-information-template-wrapper',
                    '.cookiefirst-root',
                    '#fides-overlay',
                    '.fc-consent-root',
                    '#privacy-banner',
                    '#privacy-settings',
                    '#ncmp__tool',
                    '.ncmp__banner',
                    '#ncmpconsent',
                    '.evidon-banner',
                    '#evidon-prefdiag-overlay',
                    '#SourcepointPMM',
                    '.sp_message_container',
                    '#cookiescript_injected_wrapper',
                    '#ez-cookie-dialog-wrapper',
                    '.ez-cookie-dialog-wrapper'
                ];
                removeList(knownSelectors);

                try {
                    document.querySelectorAll('c-wiz').forEach(function (w) {
                        var t = innerTextSnippet(w, 500);
                        if (/before you continue/i.test(t) && (/google|cookie|data to/i.test(t))) {
                            w.remove();
                            return;
                        }
                        if (hasConsentInSubtree(w, 0)) {
                            try {
                                w.remove();
                            } catch (e) {}
                        }
                    });
                } catch (e) {}

                tryClickConsentButtons();

                var acceptIds = ['L2AGLb', 'W0wltc'];
                acceptIds.forEach(function (id) {
                    var el = deepFindById(document.documentElement, id, 0) || document.getElementById(id);
                    if (el) {
                        removeAncestorsUntilLargePanel(el);
                    }
                });

                var strongConsent = /before you continue|we use cookies and data|cookies and data to|reject all|accept all|manage preferences|we value your privacy|consent to google|privacy and terms/i;
                var softConsent = /cookie|cookies|consent|gdpr|privacy/i;
                var candidates = [];

                walkElements(document.documentElement, function (el) {
                    if (el.nodeType !== 1) {
                        return;
                    }
                    var tag = el.nodeName;
                    if (tag === 'BUTTON' || (tag === 'DIV' && el.getAttribute('role') === 'button')) {
                        var label = (el.innerText || el.textContent || '').trim().slice(0, 80);
                        if (/^accept all$/i.test(label) || /^reject all$/i.test(label)) {
                            removeAncestorsUntilLargePanel(el);
                        }
                        return;
                    }
                    if (!wantTextProbe(el)) {
                        return;
                    }
                    var text = innerTextSnippet(el, 420);
                    if (text.length < 10) {
                        return;
                    }
                    var strong = strongConsent.test(text);
                    var soft = softConsent.test(text);
                    if (!strong && !soft) {
                        return;
                    }
                    var st = window.getComputedStyle(el);
                    var pos = st.position;
                    var z = parseInt(st.zIndex, 10);
                    var r = el.getBoundingClientRect();
                    var vw = window.innerWidth;
                    var vh = window.innerHeight;
                    var areaRatio = (r.width * r.height) / (vw * vh);
                    var wide = r.width >= Math.min(vw * 0.85, vw - 20);
                    var tall = r.height >= vh * 0.4;
                    var fullish = areaRatio >= 0.35 || (wide && tall);
                    var fixedLike = pos === 'fixed' || pos === 'sticky' || pos === 'absolute';
                    var highZ = isFinite(z) && z >= 100;
                    var mediumZ = isFinite(z) && z >= 40;

                    if (strong && fullish && fixedLike && mediumZ) {
                        candidates.push(el);
                        return;
                    }
                    if (strong && fixedLike && highZ && r.width >= vw * 0.35 && r.height >= vh * 0.3) {
                        candidates.push(el);
                        return;
                    }
                    if (soft && fixedLike && highZ && r.width >= Math.min(vw * 0.2, 120) && r.height >= 40 && r.height <= vh * 0.7) {
                        candidates.push(el);
                    }
                });

                try {
                    document.documentElement.classList.remove('osano-cm-prevent-scroll', 'cm-overlay-open');
                    document.body.classList.remove('osano-cm-prevent-scroll', 'cm-overlay-open');
                    document.body.style.removeProperty('overflow');
                    document.documentElement.style.removeProperty('overflow');
                    document.body.style.removeProperty('position');
                } catch (e) {}

                candidates.sort(function (a, b) {
                    var ra = a.getBoundingClientRect();
                    var rb = b.getBoundingClientRect();
                    return (rb.width * rb.height) - (ra.width * ra.height);
                });
                candidates.forEach(function (el) {
                    if (!el.isConnected) {
                        return;
                    }
                    try {
                        el.remove();
                    } catch (e2) {}
                });

                return true;
            })()
        JS;
    }
}
