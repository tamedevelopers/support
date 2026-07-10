<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\WebScraper;

/**
 * HTML and metadata returned by a {@see WebScraperEngineInterface} implementation.
 */
final class WebScraperFetchResult
{
    public function __construct(
        public readonly string $html,
        public readonly string $finalUrl,
        public readonly int $httpStatus,
        public readonly string $engineName,
    ) {
    }
}
