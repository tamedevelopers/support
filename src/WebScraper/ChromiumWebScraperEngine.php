<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\WebScraper;

use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Page;
use RuntimeException;
use Tamedevelopers\Support\ChromePdf\Traits\ChromeBinaryTrait;
use Throwable;

/**
 * Fetches the live DOM from headless Chromium (JavaScript, CSR, etc.), same family as
 * {@see \Tamedevelopers\Support\ChromePdf\ChromePdf}. Requires {@code chrome-php/chrome} and a Chrome/Chromium binary
 * ({@code CHROME_PATH} / autodetect via {@see \Tamedevelopers\Support\ChromePdf\ChromiumEnvironment}).
 */
final class ChromiumWebScraperEngine implements WebScraperEngineInterface
{
    use ChromeBinaryTrait;

    /**
     * @param string|null $chromiumBinary
     */
    public function __construct(?string $chromiumBinary = null)
    {
        $this->chromiumBinary = $chromiumBinary;
        $this->desktopViewportWidth = 1920;
        $this->desktopViewportHeight = 1080;
    }

    public function getName(): string
    {
        return 'chromium';
    }

    /**
     * @param array<string, mixed> $options
     */
    public function fetch(string $url, array $options = []): WebScraperFetchResult
    {
        $this->sourceMode = 'url';
        $this->sourceValue = $url;
        $this->ignoreCertificateErrors = !((bool) ($options['verify_ssl'] ?? true));

        if (!class_exists(BrowserFactory::class)) {
            throw new RuntimeException(
                'The Chromium engine requires chrome-php/chrome. Install with: composer require chrome-php/chrome'
            );
        }

        $navigationTimeoutMs = max(5000, (int) ($options['navigation_timeout_ms'] ?? 30000));
        $settleMs = max(0, (int) ($options['post_navigation_settle_ms'] ?? 1200));

        $browser = $this->acquireSharedBrowser();
        $page = null;
        $html = '';
        $finalUrl = '';

        try {
            $page = $browser->createPage();
            $page->navigate($url)->waitForNavigation(Page::LOAD, $navigationTimeoutMs);
            $this->waitForChallengePagesToClear($page, $navigationTimeoutMs);
            $this->applyPostNavigationSettle($page, $settleMs, $navigationTimeoutMs);

            $evaluation = $page->evaluate(
                'document.documentElement != null ? document.documentElement.outerHTML : (document.body != null ? document.body.innerHTML : "")'
            );
            $html = (string) $evaluation->getReturnValue(min(90000, $navigationTimeoutMs * 3));

            if (method_exists($page, 'getCurrentUrl')) {
                try {
                    $finalUrl = (string) $page->getCurrentUrl();
                } catch (Throwable) {
                }
            }
        } finally {
            if ($page !== null) {
                try {
                    $page->close();
                } catch (Throwable) {
                }
            }
        }

        if ($html === '') {
            throw new RuntimeException('Chromium returned empty HTML for ' . $url);
        }

        if ($finalUrl === '') {
            $finalUrl = $url;
        }

        return new WebScraperFetchResult($html, $finalUrl, 200, $this->getName());
    }

    /**
     * Poll until common anti-bot interstitials (Cloudflare, etc.) disappear, same idea as ChromePdf settle.
     */
    private function waitForChallengePagesToClear(Page $page, int $navigationTimeoutMs): void
    {
        $maxWaitMs = min(20000, max(4000, (int) ($navigationTimeoutMs / 2)));
        $expr = '(async function () {
            const needles = [
                "just a moment",
                "please wait",
                "checking your browser",
                "cf-challenge",
                "verify you are human",
                "attention required",
            ];
            const deadline = Date.now() + ' . $maxWaitMs . ';
            while (Date.now() < deadline) {
                const title = (document.title || "").toLowerCase();
                const html = (document.documentElement && document.documentElement.outerHTML
                    ? document.documentElement.outerHTML
                    : (document.body ? document.body.innerHTML : "")).toLowerCase();
                let blocked = false;
                for (let i = 0; i < needles.length; i++) {
                    if (title.indexOf(needles[i]) !== -1 || html.indexOf(needles[i]) !== -1) {
                        blocked = true;
                        break;
                    }
                }
                if (!blocked) {
                    return true;
                }
                await new Promise(function (r) { setTimeout(r, 400); });
            }
            return false;
        })();';

        try {
            $page->evaluate($expr)->getReturnValue($maxWaitMs + 3000);
        } catch (Throwable) {
        }
    }

    private function applyPostNavigationSettle(Page $page, int $settleMs, int $navigationTimeoutMs): void
    {
        if ($settleMs <= 0) {
            return;
        }

        $evalCap = min(15000, max(1200, $settleMs + 2500));

        try {
            $page->evaluate(
                '(async function () { await new Promise(function (r) { setTimeout(r, ' . $settleMs . '); }); })();'
            )->getReturnValue($evalCap);
        } catch (Throwable) {
        }
    }

    /**
     * Closes the shared Chromium process. Call on long-running workers when scraping is finished,
     * or rely on the registered PHP shutdown handler.
     */
    public static function shutdown(): void
    {
        if (self::$sharedBrowser !== null) {
            try {
                self::$sharedBrowser->close();
            } catch (Throwable) {
            }
            self::$sharedBrowser = null;
            self::$sharedBrowserLaunchKey = null;
        }
    }
}
