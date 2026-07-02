<?php

namespace Tamedevelopers\Support\Engines;


abstract class CachedEngine implements ExchangeEngineInterface
{
    protected ?array $staticCache = null; 
    protected int $cacheTtl = 3600; // 1 hour
    protected string $cacheFile;

    public function __construct(string $engineName)
    {
        // Unique cache file per engine
        $this->cacheFile = sys_get_temp_dir() . "/exchange_rates_{$engineName}.json";
    }

    /**
     * Abstract method that child classes must implement to handle their raw API call.
     */
    abstract protected function fetchFromApi(): array;

    /**
     * Reusable fast caching system shared by all engines.
     */
    protected function getRatesWithCaching(): array
    {
        if ($this->staticCache !== null) {
            return $this->staticCache;
        }

        if (file_exists($this->cacheFile) && (time() - filemtime($this->cacheFile) < $this->cacheTtl)) {
            $cachedData = json_decode(file_get_contents($this->cacheFile), true);
            if (isset($cachedData['rates'])) {
                $this->staticCache = $cachedData['rates'];
                return $this->staticCache;
            }
        }

        $rates = $this->fetchFromApi();
        $this->staticCache = $rates;
        return $rates;
    }
}