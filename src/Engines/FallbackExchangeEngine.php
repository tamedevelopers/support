<?php

namespace Tamedevelopers\Support\Engines;

use Exception;

class FallbackExchangeEngine implements ExchangeEngineInterface
{
    /** @var ExchangeEngineInterface[] */
    protected array $engines;

    public function __construct(array $engines)
    {
        if (empty($engines)) {
            throw new Exception('FallbackExchangeEngine requires at least one exchange engine.');
        }

        foreach ($engines as $engine) {
            if (!($engine instanceof ExchangeEngineInterface)) {
                throw new Exception('All fallback entries must implement ExchangeEngineInterface.');
            }
        }

        $this->engines = $engines;
    }

    public function getRate(string $from, string $to): float
    {
        $errors = [];

        foreach ($this->engines as $engine) {
            try {
                return $engine->getRate($from, $to);
            } catch (Exception $exception) {
                $errors[] = sprintf('%s: %s', get_class($engine), $exception->getMessage());
            }
        }

        throw new Exception('All exchange engines failed. Errors: ' . implode(' | ', $errors));
    }
}
