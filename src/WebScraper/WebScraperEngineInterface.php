<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\WebScraper;

/**
 * Fetches remote HTML for {@see \Tamedevelopers\Support\WebScraper}. The default engine uses cURL + libxml;
 * the Chromium engine uses headless Chrome (same stack as {@see \Tamedevelopers\Support\ChromePdf\ChromePdf}).
 */
interface WebScraperEngineInterface
{
    /**
     * Engine identifier (e.g. "dom", "chromium").
     */
    public function getName(): string;

    /**
     * @param array<string, mixed> $options Engine-specific options (timeouts, binary path, etc.)
     */
    public function fetch(string $url, array $options = []): WebScraperFetchResult;
}
