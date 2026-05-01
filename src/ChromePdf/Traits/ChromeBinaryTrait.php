<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\ChromePdf\Traits;

use HeadlessChromium\Browser;
use HeadlessChromium\BrowserFactory;
use Tamedevelopers\Support\ChromePdf\ChromiumEnvironment;
use Throwable;


trait ChromeBinaryTrait
{   
    /** The source mode. */
    private ?string $sourceMode = null;

    /** The source value. */
    private ?string $sourceValue = null;

    /** The Chromium binary path. */
    private ?string $chromiumBinary = null;

    /** The desktop viewport width. */
    private int $desktopViewportWidth = 1920;

    /** The desktop viewport height. */
    private int $desktopViewportHeight = 1080;

    /** @see registerShutdownHandlerOnce() */
    private static bool $shutdownHandlerRegistered = false;

    /** @see acquireSharedBrowser() */
    private static ?Browser $sharedBrowser = null;

    /** @see browserLaunchKey() */
    private static ?string $sharedBrowserLaunchKey = null;

    /**
     * When true, Chromium ignores TLS certificate errors (useful for intranet HTTPS during development).
     */
    private bool $ignoreCertificateErrors = false;

    /**
     * Maps to BrowserFactory {@code enableImages} ({@code --blink-settings=imagesEnabled=false} when false).
     * Default true — call {@see loadRemoteImages(true)} to load remote bitmap/CSS images.
     */
    private bool $enableRemoteImageLoading = true;

    /**
     * Ignore certificate errors.
     *
     * @param bool $enable
     * @return self
     */
    public function ignoreCertificateErrors(bool $enable = true): self
    {
        $this->ignoreCertificateErrors = $enable;

        return $this;
    }

    /**
     * Controls whether Chromium loads images (required for {@code http(s)://} in {@code img src} and CSS background images).
     * Default is {@code false}; pass {@code true} to opt in. Web fonts and document text are not gated by this flag.
     */
    public function loadRemoteImages(bool $enable = true): self
    {
        $this->enableRemoteImageLoading = $enable;

        return $this;
    }

    /**
     * Alias for {@see loadRemoteImages()}.
     */
    public function absoluteImageLinks(bool $enable = true): self
    {
        return $this->loadRemoteImages($enable);
    }

    /**
     * chrome-php only forwards {@code noSandbox} and {@code customFlags} to Chromium — an {@code args} key is ignored
     * (see {@code HeadlessChromium\Browser\BrowserProcess::getArgsFromOptions()}).
     */
    private function headlessRestrictEnv(array &$launch): void
    {
        if ($launch === []) {
            return;
        }

        $launch['noSandbox'] = true;

        $launch['customFlags'] = array_values(array_merge($launch['customFlags'] ?? [], [
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--no-zygote',
            '--disable-software-rasterizer',
            '--disable-crash-reporter',
            '--crash-dumps-dir=/tmp',
            '--max_old_space_size=512', // Reduce memory usage

            // Speed optimizations
            '--disable-gpu', // Disable GPU (faster in headless)
            '--disable-extensions',
            '--disable-plugins',
            '--disable-default-apps',
            '--disable-sync',
            '--disable-translate',
            '--disable-features=TranslateUI,BlinkGenPropertyTrees',
            '--disable-component-extensions-with-background-pages',
            '--disable-client-side-phishing-detection',
            '--disable-popup-blocking',
            '--disable-prompt-on-repost',
            '--disable-hang-monitor',
            '--disable-ipc-flooding-protection',
            '--disable-throttle-iframe-legacy',
            '--disable-accelerated-2d-canvas',
            '--disable-accelerated-jpeg-decoding',
            '--disable-accelerated-mjpeg-decode',
            '--disable-accelerated-video-decode',

            // flags to reduce file system interactions
            '--disable-features=WinRetrieveSuggestionsOnlyOnDemand',
            '--disable-background-networking',
            '--disk-cache-size=1',
            '--media-cache-size=1',
        ]));

        $launch['ignoreCertificateErrors'] = $this->ignoreCertificateErrors;
        $launch['keepAlive'] = true;
    }

    /**
     * Register the shutdown handler once.
     *
     * @return void
     */
    private static function registerShutdownHandlerOnce(): void
    {
        if (self::$shutdownHandlerRegistered) {
            return;
        }

        register_shutdown_function(static function (): void {
            self::shutdown();
        });

        self::$shutdownHandlerRegistered = true;
    }

    /**
     * Acquire the shared browser.
     *
     * @return Browser
     */
    private function acquireSharedBrowser(): Browser
    {
        self::registerShutdownHandlerOnce();

        $key = $this->browserLaunchKey();

        if (self::$sharedBrowser !== null && self::$sharedBrowserLaunchKey === $key) {
            return self::$sharedBrowser;
        }

        if (self::$sharedBrowser !== null) {
            try {
                self::$sharedBrowser->close();
            } catch (Throwable $e) {
            }
            self::$sharedBrowser = null;
            self::$sharedBrowserLaunchKey = null;
        }

        [$binary, $launch] = $this->buildBrowserLaunchConfig();

        $this->headlessRestrictEnv($launch);
        
        $factory = new BrowserFactory($binary);
        self::$sharedBrowser = $factory->createBrowser($launch);
        self::$sharedBrowserLaunchKey = $key;

        return self::$sharedBrowser;
    }

    /**
     * Build the browser launch config.
     *
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function buildBrowserLaunchConfig(): array
    {
        $env = new ChromiumEnvironment();
        $binary = $this->chromiumBinary ?? $env->resolveChromeBinary();
        $launch = $env->getLaunchOptions();

        if ($this->ignoreCertificateErrors) {
            $launch['ignoreCertificateErrors'] = true;
        }

        $launch['enableImages'] = $this->shouldEnableChromiumImages();
        $launch['keepAlive'] = true;

        if ($this->sourceMode === 'url') {
            // Set desktop hints at browser startup to reduce per-page emulation overhead.
            $launch['windowSize'] = [$this->desktopViewportWidth, $this->desktopViewportHeight];
            $launch['userAgent'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 '
            . '(KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36';
        }

        return [$binary, $launch];
    }

    /**
     * Build the browser launch key.
     *
     * @return string
     */
    private function browserLaunchKey(): string
    {
        [$binary, $launch] = $this->buildBrowserLaunchConfig();

        $this->headlessRestrictEnv($launch);

        return implode("\0", [
            (string) $binary,
            ($launch['enableImages'] ?? true) ? '1' : '0',
            ($launch['ignoreCertificateErrors'] ?? false) ? '1' : '0',
            (string) ($launch['userAgent'] ?? ''),
            (string) ($launch['windowSize'][0] ?? ''),
            (string) ($launch['windowSize'][1] ?? ''),
        ]);
    }

    /**
     * Should enable Chromium images.
     *
     * @return bool
     */
    private function shouldEnableChromiumImages(): bool
    {
        return $this->enableRemoteImageLoading;
    }

}
