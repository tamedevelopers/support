<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\ChromePdf\Internal;

/**
 * Removes common full-page / overlay preloaders after {@code DOMContentLoaded} (one fast {@code evaluate()}).
 *
 * Intentionally no {@code Page.addScriptToEvaluateOnNewDocument} hook: a subtree {@code MutationObserver} plus
 * polling during hydration was slowing real sites by seconds (main-thread work on every DOM mutation).
 */
final class PreloaderRemovalScript
{
    /**
     * Single fast {@code evaluate()} right after navigation / {@code setHtml} — no {@code load} wait.
     */
    public static function asImmediateStripExpression(): string
    {
        $js = "(function () {";
        $js .= "const killSelectors = [
            '#nprogress', '.nprogress', '#nprogress .bar', '#nprogress .peg',
            '#loading', '#page-loading', '#page-loader', '.page-loader', '.page-loading',
            '.loading-screen', '.loading-overlay', '.loading__overlay', '.app-loading',
            '[data-loading][data-loading=\"true\"]', '[data-testid=\"loading\"]', '[data-testid=\"loader\"]',
            '.MuiCircularProgress-root', '.MuiLinearProgress-root', '.MuiBackdrop-invisible',
            '.spinner', '.spinner-overlay', '.global-loader', '.site-loader', '.splash-screen',
            '.pace', '.pace-progress', '.pace-activity',
            '.ReactModal__Overlay--loading', '.ant-spin-nested-loading > .ant-spin-container .ant-spin-blur',
            '.ant-spin-fullscreen', '.el-loading-mask', '.v-overlay__scrim',
            '.ngx-spinner-overlay', '.ngx-overlay', 'app-root .loading', '.sf-loader',
            '[class*=\"Skeleton\"]', '[class*=\"skeleton-screen\"]', '.skeleton-loader',
            '[data-preloader]', '[data-page-loader]', '.preloader', '#preloader', '.page-preloader',
            '.wp-preloader', '#wp-preloader', '.loader-wrapper', '.initial-loader', '#app-loader'
        ];";

        $js .= "const alwaysRemove = [
            '.ant-spin-fullscreen', '.ant-spin-blur', '.ngx-spinner-overlay', '.ngx-overlay',
            '.el-loading-mask', '.v-overlay__scrim'
        ];";

        $js .= "try {
            if (!document.documentElement) return;

            killSelectors.forEach(sel => {
                document.querySelectorAll(sel).forEach(n => {
                    try {
                        const st = window.getComputedStyle(n);
                        const r = n.getBoundingClientRect();
                        const fixedLike = ['fixed', 'sticky', 'absolute'].includes(st.position);
                        const big = r.width >= window.innerWidth * 0.25 && r.height >= window.innerHeight * 0.18;
                        
                        if ((fixedLike && big) || /nprogress|pace|MuiCircular|LinearProgress/.test(sel)) {
                            n.remove();
                        }
                    } catch (e) {}
                });
            });

            alwaysRemove.forEach(sel => {
                document.querySelectorAll(sel).forEach(n => { try { n.remove(); } catch (e) {} });
            });

            document.documentElement.classList.remove(
                'nprogress-busy', 'loading', 'is-loading', 'pace-running', 'splashing', 'preloader-active', 'wp-loading'
            );
            
            if (document.body) {
                document.body.classList.remove('loading', 'is-loading', 'nprogress-busy', 'pace-done', 'app-loading');
                document.body.style.removeProperty('overflow');
                document.body.style.removeProperty('position');
            }
            document.documentElement.style.removeProperty('overflow');
        } catch (e) {}";

        $js .= "return true; })();";

        return $js;
    }
    
}
