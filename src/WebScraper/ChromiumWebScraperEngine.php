<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\WebScraper;

use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Communication\Message;
use HeadlessChromium\Page;
use RuntimeException;
use Tamedevelopers\Support\ChromePdf\Internal\ChromiumStealthScript;
use Tamedevelopers\Support\ChromePdf\Internal\ScraperPageScript;
use Tamedevelopers\Support\ChromePdf\Traits\ChromeBinaryTrait;
use Throwable;

/**
 * Fetches the live DOM from headless Chromium (JavaScript, CSR, etc.), same family as
 * {@see \Tamedevelopers\Support\ChromePdf\ChromePdf}. Requires {@code chrome-php/chrome} and a Chrome/Chromium binary
 * ({@code CHROME_PATH} / autodetect via {@see ChromiumEnvironment}).
 */
final class ChromiumWebScraperEngine implements WebScraperEngineInterface
{
    use ChromeBinaryTrait;

    public function __construct(?string $chromiumBinary = null)
    {
        $this->chromiumBinary = $chromiumBinary;
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

        if (!class_exists(BrowserFactory::class)) {
            throw new RuntimeException(
                'The Chromium engine requires chrome-php/chrome. Install with: composer require chrome-php/chrome'
            );
        }

        $cloudflareWait = (int) ($options['cloudflare_wait_ms'] ?? 1500);
        $priceWait = (int) ($options['price_hydration_wait_ms'] ?? 600);
        $evalTimeout = (int) ($options['evaluate_timeout_ms'] ?? ($cloudflareWait + $priceWait + 2000));

        $browser = $this->acquireSharedBrowser();
        $finalUrl = $url;
        $page = null;
        $html = '';

        try {
            $page = $browser->createPage();
            $this->installStealthScript($page);

            $page->navigate($url);

            $html = (string) $page->evaluate(
                ScraperPageScript::asExpression($cloudflareWait, $priceWait)
            )->getReturnValue(min(4500, max(3200, $evalTimeout)));

            if (method_exists($page, 'getCurrentUrl')) {
                try {
                    $finalUrl = (string) $page->getCurrentUrl(2000);
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

    private function installStealthScript(Page $page): void
    {
        try {
            $page->getSession()->sendMessageSync(new Message(
                'Page.addScriptToEvaluateOnNewDocument',
                ['source' => ChromiumStealthScript::source()]
            ));
        } catch (Throwable) {
        }
    }

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
