<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use Exception;
use Tamedevelopers\Support\Env;
use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Process\Http;
use Tamedevelopers\Support\Engines\EcbEngine;
use Tamedevelopers\Support\Engines\NbpEngine;
use Tamedevelopers\Support\Engines\ErApiEngine;
use Tamedevelopers\Support\Collections\Collection;
use Tamedevelopers\Support\Engines\FloatRatesEngine;
use Tamedevelopers\Support\Engines\OpenExchangeEngine;
use Tamedevelopers\Support\Engines\ExchangeRateHostEngine;
use Tamedevelopers\Support\Engines\FallbackExchangeEngine;
use Tamedevelopers\Support\Engines\ExchangeEngineInterface;

class Exchange
{
    protected ExchangeEngineInterface $engine;
    protected mixed $baseEngine = null;

    /**
     * Inject the desired conversion engine.
     */
    public function __construct()
    {
        $this->engine = new FallbackExchangeEngine([
            new EcbEngine(),           // European Central Bank - EUR base, daily updates, no auth
            new ErApiEngine(),         // ER-API - Good coverage
            new NbpEngine(),           // National Bank of Poland - PLN base, extensive coverage
            new FloatRatesEngine(),    // FloatRates - Web-scraped, good availability
        ]);
    }

    /**
     * Get first engine
     */
    public function first(): mixed
    {
        return (new Collection($this->getEngines()))->first();
    }

    /**
     * Get the base URL
     */
    public function baseUrl(): string|null
    {
        return $this->first()?->getBase() ?: null;
    }

    /**
     * Get the base Engine
     */
    public function baseEngine(): string|null
    {
        if(empty($this->baseEngine)){
            $this->baseEngine = $this->first();
        }
        
        return class_basename($this->baseEngine);
    }

    /**
     * Swap or update the engine dynamically if needed.
     * 
     * @param 'EcbEngine'|'NbpEngine'|'ErApiEngine'|'FloatRatesEngine'|ExchangeEngineInterface $engine
     */
    public function setEngine($engine): self
    {
        if(is_string($engine)){
            $this->engine = match (Str::lower($engine)) {
                'npb', 'nbpengine' => new NbpEngine(),
                'erapi', 'erapiengine' => new ErApiEngine(),
                'floatrates', 'floatratesengine' => new FloatRatesEngine(),
                default => new EcbEngine(),
            };
        } else{
            $this->engine = $engine;
        }

        $this->baseEngine = $this->engine;
        
        return $this;
    }

    /**
     * Get Engines
     */
    public function getEngines(): array
    {
        $engines = $this->engine instanceof FallbackExchangeEngine
            ? $this->engine->getEngines()
            : [$this->engine];

        return $engines;
    }

    /**
     * Set the exchange rate engine to FloatRates.
     * - Supports Higher Exchange rates
     * @link https://www.floatrates.com
     */
    public function highExchange(): self
    {
        return $this->setEngine(
            new FloatRatesEngine()
        );
    }

    /**
     * Set the exchange rate engine to OpenExchangeRates.
     *
     * @link https://openexchangerates.org
     * @param  string|null  $apiKey
     */
    public function openExchange(?string $apiKey = null): self
    {
        return $this->setEngine(
            new OpenExchangeEngine($apiKey)
        );
    }

    /**
     * Set the exchange rate engine to ExchangeRateHost.
     *
     * @link https://exchangerate.host/
     * @param  string|null  $apiKey
     */
    public function exchangeRate(?string $apiKey = null): self
    {
        return $this->setEngine(
            new ExchangeRateHostEngine($apiKey)
        );
    }

    /**
     * Get Live Rates
     */
    public function rates(): array
    {
        $engines = $this->getEngines();
        $verifySsl = Env::environment(['live', 'production'], true);
        
        foreach ($engines as $engine) {
            try {
                // Pass $verifySsl into the HTTP request
                $response = Http::withOptions([
                    'verify' => $verifySsl,
                ])->get($engine->getBase());

                $this->baseEngine = $engine;

                if ($response->status() === 200) {
                    return $response->json();
                }
            } catch (\Throwable $th) {
                // Try next fallback engine on failure
                continue;
            }
        }

        return [];
    }

    /**
     * Get rates between two currencies.
     *
     * @param string $from Currency code (e.g., 'USD')
     * @param string $to Currency code (e.g., 'EUR')
     * @return float The rates value
     * @throws Exception
     */
    public function rate($from, $to): float
    {
        $engines = $this->getEngines();

        foreach ($engines as $engine) {
            try {
                $rate = $engine->getRate($from, $to);

                $this->baseEngine = $engine;

                if ($rate > 0) {
                    return $rate;
                }
            } catch (\Throwable $th) {
                // Try next fallback engine on failure
                continue;
            }
        }

        return 0;
    }

    /**
     * Automatically convert an amount from one currency to another.
     *
     * @param string $from Currency code (e.g., 'USD')
     * @param string $to Currency code (e.g., 'EUR')
     * @param float $amount The amount to convert
     * @param float|int $sum Additional added value
     * @param bool $format If amount should be formatted or not
     * @return float|string The converted amount
     * @throws Exception
     */
    public function convert($from, $to, $amount, $sum = 0, $format = false)
    {
        if ($amount < 0) {
            throw new Exception("Amount cannot be negative.");
        }

        if (strtoupper($from) === strtoupper($to)) {
            return $amount;
        }

        $rate = $this->rate($from, $to);

        $total = round($amount * $rate, 4) + $sum;

        return $format ? number_format($total, 2) : $total;
    }

}