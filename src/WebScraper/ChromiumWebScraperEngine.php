<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\WebScraper;

use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Page;
use RuntimeException;
use Tamedevelopers\Support\ChromePdf\ChromiumEnvironment;
use Throwable;

/**
 * Fetches the live DOM from headless Chromium (JavaScript, CSR, etc.), same family as
 * {@see \Tamedevelopers\Support\ChromePdf\ChromePdf}. Requires {@code chrome-php/chrome} and a Chrome/Chromium binary
 * ({@code CHROME_PATH} / autodetect via {@see ChromiumEnvironment}).
 */
final class ChromiumWebScraperEngine implements WebScraperEngineInterface
{
    public function getName(): string
    {
        return 'chromium';
    }

    public function fetch(string $url, array $options = []): WebScraperFetchResult
    {
        if (!class_exists(BrowserFactory::class)) {
            throw new RuntimeException(
                'The Chromium engine requires chrome-php/chrome. Install with: composer require chrome-php/chrome'
            );
        }

        $env = new ChromiumEnvironment();
        $binary = isset($options['binary']) && is_string($options['binary']) && $options['binary'] !== ''
            ? (string) $options['binary']
            : $env->resolveChromeBinary();

        $launch = $env->getLaunchOptions();
        $launch['noSandbox'] = true;
        $launch['keepAlive'] = false;
        $launch['ignoreCertificateErrors'] = (bool) ($options['ignore_certificate_errors'] ?? true);
        if (isset($options['user_agent']) && is_string($options['user_agent']) && $options['user_agent'] !== '') {
            $launch['userAgent'] = (string) $options['user_agent'];
        }
        $launch['customFlags'] = array_values(array_merge($launch['customFlags'] ?? [], [
            '--disable-dev-shm-usage',
        ]));

        $navTimeout = (int) ($options['navigation_timeout_ms'] ?? 45000);
        $evalTimeout = (int) ($options['evaluate_timeout_ms'] ?? min(90_000, max(10_000, (int) ($navTimeout * 1.5))));

        $waitFor = (($options['wait_for'] ?? 'domcontent') === 'load') ? Page::LOAD : Page::DOM_CONTENT_LOADED;

        $factory = new BrowserFactory($binary);
        $browser = null;
        $html = '';
        $finalUrl = $url;

        try {
            $browser = $factory->createBrowser($launch);
            $page = $browser->createPage();
            $page->navigate($url)->waitForNavigation($waitFor, $navTimeout);
            $evaluation = $page->evaluate(
                'document.documentElement != null ? document.documentElement.outerHTML : (document.body != null ? document.body.innerHTML : "")'
            );
            $html = (string) $evaluation->getReturnValue($evalTimeout);

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
}
