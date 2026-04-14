<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\ChromePdf\Internal;

/**
 * Async in-page settle: short wait for readiness, strip loaders / false “offline” banners, then paint.
 * Does not block on {@code document.readyState === 'complete'} for the full budget — many sites never reach it
 * quickly in headless mode (analytics, beacons), which previously burned ~12s and triggered CDP timeouts.
 */
final class PageStabilityScript
{
    /**
     * @param int $maxWaitMs soft upper bound for the whole async IIFE (phases are capped below this)
     */
    public static function asSettleExpression(int $maxWaitMs): string
    {
        $max = max(400, min(20000, $maxWaitMs));

        return <<<JS
            (async function () {
                var maxWait = {$max};
                var t0 = Date.now();
                function sleep(ms) { return new Promise(function (r) { setTimeout(r, ms); }); }
                function elapsed() { return Date.now() - t0; }

                var completeCap = Math.min(2800, maxWait);
                while (document.readyState !== 'complete' && elapsed() < completeCap) {
                    await sleep(Math.min(80, Math.max(5, completeCap - elapsed())));
                }

                var busyBudget = Math.min(1200, Math.max(0, maxWait - elapsed()));
                var busyEnd = Date.now() + busyBudget;
                while (document.documentElement.getAttribute('aria-busy') === 'true' && Date.now() < busyEnd) {
                    await sleep(50);
                }
                if (document.body && document.body.getAttribute('aria-busy') === 'true') {
                    busyEnd = Date.now() + Math.min(800, Math.max(0, maxWait - elapsed()));
                    while (document.body.getAttribute('aria-busy') === 'true' && Date.now() < busyEnd) {
                        await sleep(50);
                    }
                }

                var killSelectors = [
                    '#nprogress', '.nprogress', '#nprogress .bar', '#nprogress .peg',
                    '#loading', '#page-loading', '#page-loader', '.page-loader', '.page-loading',
                    '.loading-screen', '.loading-overlay', '.loading__overlay', '.app-loading',
                    '[data-loading][data-loading="true"]', '[data-testid="loading"]', '[data-testid="loader"]',
                    '.MuiCircularProgress-root', '.MuiLinearProgress-root', '.MuiBackdrop-invisible',
                    '.spinner', '.spinner-overlay', '.global-loader', '.site-loader', '.splash-screen',
                    '.pace', '.pace-progress', '.pace-activity',
                    '.ReactModal__Overlay--loading', '.ant-spin-nested-loading > .ant-spin-container .ant-spin-blur',
                    '.ant-spin-fullscreen', '.el-loading-mask', '.v-overlay__scrim',
                    '.ngx-spinner-overlay', '.ngx-overlay', 'app-root .loading', '.sf-loader',
                    '[class*="Skeleton"]', '[class*="skeleton-screen"]', '.skeleton-loader'
                ];

                function safeRemove(sel) {
                    try {
                        document.querySelectorAll(sel).forEach(function (n) {
                            try {
                                var st = window.getComputedStyle(n);
                                var r = n.getBoundingClientRect();
                                var fixedLike = st.position === 'fixed' || st.position === 'sticky' || st.position === 'absolute';
                                var big = r.width >= window.innerWidth * 0.25 && r.height >= window.innerHeight * 0.18;
                                if (fixedLike && big) {
                                    n.remove();
                                    return;
                                }
                                if (sel.indexOf('nprogress') !== -1 || sel.indexOf('pace') !== -1 || sel.indexOf('MuiCircular') !== -1 || sel.indexOf('LinearProgress') !== -1) {
                                    n.remove();
                                }
                            } catch (e) {}
                        });
                    } catch (e2) {}
                }

                killSelectors.forEach(safeRemove);

                var alwaysRemoveSelectors = [
                    '.ant-spin-fullscreen', '.ant-spin-blur', '.ngx-spinner-overlay', '.ngx-overlay',
                    '.el-loading-mask', '.v-overlay__scrim'
                ];
                alwaysRemoveSelectors.forEach(function (sel) {
                    try {
                        document.querySelectorAll(sel).forEach(function (n) {
                            try {
                                n.remove();
                            } catch (eR) {}
                        });
                    } catch (eS) {}
                });

                try {
                    document.querySelectorAll('[role="progressbar"]').forEach(function (el) {
                        try {
                            var st = window.getComputedStyle(el);
                            var r = el.getBoundingClientRect();
                            if ((st.position === 'fixed' || st.position === 'sticky') && r.width >= 40 && r.height >= 40) {
                                var p = el.parentElement;
                                if (p && p !== document.body) {
                                    var pr = p.getBoundingClientRect();
                                    if (pr.width >= window.innerWidth * 0.3 && pr.height >= window.innerHeight * 0.2) {
                                        p.remove();
                                    }
                                }
                            }
                        } catch (e3) {}
                    });
                } catch (e4) {}

                try {
                    document.querySelectorAll('div,section,aside,p').forEach(function (el) {
                        try {
                            var t = (el.innerText || el.textContent || '').trim().slice(0, 220);
                            if (!t) return;
                            if (!/離線|offline|not connected|处于离线|互聯網連接|internet connection/i.test(t)) return;
                            var st = window.getComputedStyle(el);
                            var r = el.getBoundingClientRect();
                            if ((st.position === 'fixed' || st.position === 'sticky') && r.height > 0 && r.height < window.innerHeight * 0.45) {
                                el.remove();
                            }
                        } catch (e5) {}
                    });
                } catch (e6) {}

                await new Promise(function (resolve) {
                    requestAnimationFrame(function () {
                        requestAnimationFrame(function () {
                            resolve();
                        });
                    });
                });
                await sleep(Math.min(80, Math.max(5, maxWait - elapsed())));

                return true;
            })()
        JS;
    }
}
