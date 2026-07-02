<?php

namespace Tamedevelopers\Support\Engines;

interface ExchangeEngineInterface
{
    /**
     * Get the exchange rate from one currency to another.
     *
     * @param string $from
     * @param string $to
     * @return float
     */
    public function getRate(string $from, string $to): float;
}