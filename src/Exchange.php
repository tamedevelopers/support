<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use Exception;
use Tamedevelopers\Support\Engines\EcbEngine;
use Tamedevelopers\Support\Engines\ErApiEngine;
use Tamedevelopers\Support\Engines\ExchangeEngineInterface;
use Tamedevelopers\Support\Engines\ExchangeRateHostEngine;
use Tamedevelopers\Support\Engines\FallbackExchangeEngine;
use Tamedevelopers\Support\Engines\FloatRatesEngine;
use Tamedevelopers\Support\Engines\NbpEngine;
use Tamedevelopers\Support\Engines\OpenExchangeEngine;

class Exchange
{
    protected ExchangeEngineInterface $engine;

    /**
     * Inject the desired conversion engine.
     */
    public function __construct()
    {
        $this->engine = new FallbackExchangeEngine([
            new EcbEngine(),           // European Central Bank - EUR base, daily updates, no auth
            new NbpEngine(),           // National Bank of Poland - PLN base, extensive coverage
            new ErApiEngine(),         // ER-API - Good coverage
            new FloatRatesEngine(),    // FloatRates - Web-scraped, good availability
        ]);
    }

    /**
     * Swap or update the engine dynamically if needed.
     */
    public function setEngine(ExchangeEngineInterface $engine): self
    {
        $this->engine = $engine;
        
        return $this;
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
     * Get rates between two currencies.
     *
     * @param string $from Currency code (e.g., 'USD')
     * @param string $to Currency code (e.g., 'EUR')
     * @return float The rates value
     * @throws Exception
     */
    public function rate(string $from, string $to): float
    {
        return $this->engine->getRate($from, $to);
    }

    /**
     * Automatically convert an amount from one currency to another.
     *
     * @param string $from Currency code (e.g., 'USD')
     * @param string $to Currency code (e.g., 'EUR')
     * @param float $amount The amount to convert
     * @param bool $format If amount should be formatted or not
     * 
     * @return float|string The converted amount
     * @throws Exception
     */
    public function convert(string $from, string $to, float $amount, bool $format = false)
    {
        if ($amount < 0) {
            throw new Exception("Amount cannot be negative.");
        }

        if (strtoupper($from) === strtoupper($to)) {
            return $amount;
        }

        $rate = $this->rate($from, $to);

        $total = round($amount * $rate, 4);

        return $format ? number_format($total, 2) : $total;
    }

}