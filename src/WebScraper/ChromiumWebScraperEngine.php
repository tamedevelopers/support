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

        // Use the exact class instantiation pattern your PDF system uses
        $env = new ChromiumEnvironment();
        $binary = $this->chromiumBinary ?? $env->resolveChromeBinary();
        $factory = new BrowserFactory($binary);

        // Core array configuration mimicking the PDF launch environment properties
        $launchOptions = [
            'headless'       => true,
            'startupTimeout' => 30,
            'noSandbox'      => true,
            'keepAlive'      => true,
            'windowSize'     => [1920, 1080],
            'userAgent'      => $options['user_agent'] ?? 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36',
            'customFlags'    => [
                '--disable-blink-features=AutomationControlled',
                '--no-first-run',
                '--disable-extensions',
                '--disable-setuid-sandbox',
                '--no-zygote',
                '--disable-dev-shm-usage',
                '--disable-gpu',
                // Additional stealth flags to counter fingerprinting blocks
                '--lang=en-US,en;q=0.9',
                '--disable-browser-side-navigation',
                '--disable-features=IsolateOrigins,site-per-process',
            ],
        ];

        // Add proxy server arguments if provided in options
        if (!empty($options['proxy'])) {
            $launchOptions['customArgs'][] = '--proxy-server=' . $options['proxy'];
        } else {
            // Smart Fallback Detection: Check if a local Tor routing proxy is alive on the host machine
            // $torSocket = @fsockopen('127.0.0.1', 9050, $errno, $errstr, 0.5);
            // if ($torSocket) {
            //     $launchOptions['customFlags'][] = '--proxy-server=socks5://127.0.0.1:9050';
            //     fclose($torSocket);
            // } elseif ($envProxy = getenv('HTTP_PROXY') ?: getenv('http_proxy')) {
            //     // Fall back to system-wide profile proxy configurations if present
            //     $launchOptions['customFlags'][] = '--proxy-server=' . $envProxy;
            // }
        }

        // Run your environment alignment method to safe-check remaining keys
        if (method_exists($env, 'headlessRestrictEnv')) {
            $env->headlessRestrictEnv($launchOptions);
        }

        // Create the custom container-safe browser process
        $browser = $factory->createBrowser($launchOptions);
        $finalUrl = '';

        try {
            $page = $browser->createPage();

            $page->navigate($url)->waitForNavigation(Page::LOAD, 30000);

            $evaluation = $page->evaluate(
                'document.documentElement != null ? document.documentElement.outerHTML : (document.body != null ? document.body.innerHTML : "")'
            );
            $html = (string) $evaluation->getReturnValue(90000);

            if (method_exists($page, 'getCurrentUrl')) {
                try {
                    $finalUrl = (string) $page->getCurrentUrl();
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
