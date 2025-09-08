<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Capsule;

/**
 * Packages can implement this interface and list their provider class
 * in composer.json under:
 *
 *   "extra": {
 *     "tamedevelopers": {
 *       "providers": [
 *         "Vendor\\Package\\Console\\MyCommands"
 *       ]
 *     }
 *   }
 *
 * The provider's register() will receive the shared Artisan instance and
 * should call $artisan->register(...) for each command.
 */
interface CommandProviderInterface
{
    
    /**
     * Register package commands into the shared Artisan registry.
     */
    public function register(Artisan $artisan): void;
}