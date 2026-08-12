<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Process;

use Illuminate\Http\Client\Factory;

class Http extends Factory
{
    /**
     * Create a new instance of your custom PendingRequest.
     *
     * @return \Tamedevelopers\Support\Process\PendingRequest
     */
    public function newPendingRequest(): PendingRequest
    {
        return new PendingRequest($this);
    }

    /**
     * Dynamically proxy static calls to a new custom PendingRequest instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        $instance = new static();

        return $instance->newPendingRequest()->$method(...$parameters);
    }
}