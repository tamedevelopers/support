<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\ChromePdf\Internal;

/**
 * Removes common floating UI that should not appear in PDFs: chat widgets, messenger bubbles,
 * WhatsApp buttons, support launchers, and back-to-top controls.
 */
final class FloatingElementRemovalScript
{

    public static function asExpression(): string
    {
        return <<<'JS'
            (function () {
                function removeMatches(sel) {
                    try {
                        document.querySelectorAll(sel).forEach(function (el) {
                            try {
                                el.remove();
                            } catch (e) {}
                        });
                    } catch (e2) {}
                }

                var knownSelectors = [
                    '#fb-root', '.fb_dialog', '.fb-customerchat', '[class*="fb_dialog"]',
                    '#crisp-chatbox', '[class*="crisp-client"]',
                    '#tidio-chat', '[id*="tidio"]', '[class*="tidio"]',
                    '#intercom-container', '[class*="intercom-"]', '[class*="intercom-launcher"]',
                    '#hubspot-messages-iframe-container', '[class*="hubspot-messages"]',
                    '#drift-widget-container', '[class*="drift-"]',
                    '#chat-widget-container', '[class*="livechat"]', '[id*="livechat"]',
                    '[class*="tawk"]', '[id*="tawk"]',
                    '[class*="zendesk"]', '[id*="zendesk"]',
                    '[class*="zopim"]', '[id*="zopim"]',
                    '[class*="jivo"]', '[id*="jivo"]',
                    '[class*="chaport"]', '[id*="chaport"]',
                    '[class*="whatsapp"]', '[id*="whatsapp"]',
                    'a[href*="wa.me/"]', 'a[href*="api.whatsapp.com"]',
                    '[class*="messenger"]', '[id*="messenger"]',
                    '[class*="chat-widget"]', '[id*="chat-widget"]',
                    '[class*="chat-button"]', '[id*="chat-button"]',
                    '[class*="support-widget"]', '[id*="support-widget"]',
                    '[class*="floating-button"]', '[class*="floating-widget"]',
                    '[class*="scroll-to-top"]', '[id*="scroll-to-top"]',
                    '[class*="back-to-top"]', '[id*="back-to-top"]',
                    '[aria-label*="chat" i]', '[aria-label*="whatsapp" i]',
                    '[aria-label*="messenger" i]', '[aria-label*="back to top" i]'
                ];

                knownSelectors.forEach(removeMatches);

                try {
                    var vw = window.innerWidth;
                    var vh = window.innerHeight;
                    document.querySelectorAll('div,aside,section,a,button,iframe').forEach(function (el) {
                        try {
                            if (!el || el.nodeType !== 1 || !el.isConnected) return;
                            var st = window.getComputedStyle(el);
                            if (st.display === 'none' || st.visibility === 'hidden') return;
                            if (st.position !== 'fixed' && st.position !== 'sticky') return;
                            var z = parseInt(st.zIndex, 10);
                            if (!(isFinite(z) && z >= 40)) return;
                            var r = el.getBoundingClientRect();
                            if (r.width <= 0 || r.height <= 0) return;
                            if (r.width >= vw * 0.55 || r.height >= vh * 0.55) return;

                            var nearBottom = r.bottom >= vh - 140;
                            var nearRight = r.right >= vw - 140;
                            var nearLeft = r.left <= 140;
                            var compact = r.width <= 420 && r.height <= 420;
                            var smallBubble = r.width <= 120 && r.height <= 120;

                            if (!(compact && nearBottom && (nearRight || nearLeft || smallBubble))) {
                                return;
                            }

                            var blob = (
                                (el.id || '') + ' ' +
                                (typeof el.className === 'string' ? el.className : '') + ' ' +
                                (el.getAttribute('aria-label') || '') + ' ' +
                                (el.getAttribute('title') || '')
                            ).toLowerCase();

                            if (/chat|messenger|whatsapp|intercom|crisp|tawk|zendesk|support|help|livechat|launcher|back-to-top|scroll-to-top|totop/.test(blob) || smallBubble) {
                                el.remove();
                            }
                        } catch (e3) {}
                    });
                } catch (e4) {}

                return true;
            })()
        JS;
    }
}
