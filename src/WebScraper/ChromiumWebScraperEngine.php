<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\WebScraper;

use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Page;
use RuntimeException;
use Tamedevelopers\Support\ChromePdf\ChromiumEnvironment;
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

    /**
     * Create a new ChromiumWebScraperEngine instance.
     *
     * @param string|null $chromiumBinary
     */
    public function __construct(?string $chromiumBinary = null)
    {
        $this->chromiumBinary = $chromiumBinary;
    }

    /** 
     * Get the name of the engine.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'chromium';
    }

    /**
     * Fetch the HTML from the URL.
     *
     * @param string $url
     * @param array $options
     * @return WebScraperFetchResult
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

        $browser = $this->acquireSharedBrowser();

        $navTimeout = (int) ($options['navigation_timeout_ms'] ?? 45000);
        $evalTimeout = (int) ($options['evaluate_timeout_ms'] 
            ?? min(90_000, max(10_000, (int) ($navTimeout * 1.5))));

        try {
            $page = $browser->createPage();
            $page->navigate($url)->waitForNavigation(Page::LOAD, 30000);
            $evaluation = $page->evaluate(
                'document.documentElement != null ? document.documentElement.outerHTML : (document.body != null ? document.body.innerHTML : "")'
            );
            $html = (string) $evaluation->getReturnValue(90000);

            if (method_exists($page, 'getCurrentUrl')) {
                try {
                    $finalUrl = (string) $page->getCurrentUrl(min(30_000, $evalTimeout));
                } catch (Throwable) {
                }
            }
        } finally {
            if ($browser !== null) {
                try {
                    $browser->close();
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
     * Closes the shared Chromium process started by {@see generate()}. Call on long-running workers when PDF
     * generation is finished, or rely on the registered PHP shutdown handler.
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
