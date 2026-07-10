<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\WebScraper;

use HeadlessChromium\BrowserFactory;
use RuntimeException;
use Tamedevelopers\Support\ChromePdf\ChromePdf;
use Tamedevelopers\Support\ChromePdf\Exception\ConversionFailedException;

/**
 * Fetches the live DOM via {@see ChromePdf::capturePageContent()} — identical Chromium navigation to PDF capture.
 */
final class ChromiumWebScraperEngine implements WebScraperEngineInterface
{
    public function __construct(
        private ?string $chromiumBinary = null,
    ) {
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
        if (!class_exists(BrowserFactory::class)) {
            throw new RuntimeException(
                'The Chromium engine requires chrome-php/chrome. Install with: composer require chrome-php/chrome'
            );
        }

        $navigationTimeoutMs = max(3000, min(4500, (int) ($options['navigation_timeout_ms'] ?? 4500)));

        $chrome = ChromePdf::create()
            ->fromUrl($url)
            ->prioritizeSpeed(true)
            ->navigationTimeoutMs($navigationTimeoutMs);

        if ($this->chromiumBinary !== null) {
            $chrome->chromiumBinary($this->chromiumBinary);
        }

        if (!((bool) ($options['verify_ssl'] ?? true))) {
            $chrome->ignoreCertificateErrors(true);
        }

        try {
            $captured = $chrome->capturePageContent();
        } catch (ConversionFailedException $e) {
            throw new RuntimeException($e->getMessage(), (int) $e->getCode(), $e);
        }

        return new WebScraperFetchResult(
            $captured['html'],
            $captured['finalUrl'],
            200,
            $this->getName(),
        );
    }

    public static function shutdown(): void
    {
        ChromePdf::shutdown();
    }
}
