<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Capsule\Traits;

use Tamedevelopers\Support\Capsule\File;

/**
 * Discovers and registers external command providers declared by packages.
 *
 * Convention in each package's composer.json:
 * {
 *   "extra": {
 *     "tamedevelopers": {
 *       "providers": [
 *         "Vendor\\Package\\Console\\MyCommands"
 *       ]
 *     }
 *   }
 * }
 */
trait ArtisanDiscovery
{
    /**
     * Track providers that have been registered to avoid duplicate registration.
     */
    private static array $registeredProviders = [];




    /**
     * Discover providers by scanning vendor composer.json
     * No reliance on composer/composer installed.json or installed.php.
     */
    private function discoverExternal(): void
    {
        $vendorPath = $this->resolveVendorPath();
        if (!$vendorPath || !is_dir($vendorPath)) {
            return;
        }

        // Scan all package composer.json files
        $pattern = $vendorPath . DIRECTORY_SEPARATOR . 'composer.json';
        $composerFiles = glob($pattern) ?: [];

        foreach ($composerFiles as $composerJson) {
            $json = @File::get($composerJson);
            if ($json === false) {
                continue;
            }
            $meta = json_decode($json, true);
            if (!is_array($meta)) {
                continue;
            }
            $extra = $meta['extra']['tamedevelopers'] ?? null;
            if (!$extra) {
                continue;
            }
            $providers = $extra['providers'] ?? [];
            foreach ((array) $providers as $fqcn) {
                if (!is_string($fqcn) || !class_exists($fqcn)) {
                    continue;
                }
                if (isset(self::$registeredProviders[$fqcn])) {
                    continue; // already registered in this process
                }
                try {
                    $provider = new $fqcn();
                    if (method_exists($provider, 'register')) {
                        $provider->register($this);
                        self::$registeredProviders[$fqcn] = true;
                    }
                } catch (\Throwable $e) {
                    // ignore provider instantiation/registration failures
                }
            }
        }
    }

    /**
     * Resolve the vendor directory path for both dev (package root) and consumer app.
     */
    private function resolveVendorPath(): ?string
    {
        // Current file: support/Capsule/Traits/ArtisanDiscovery.php
        $packageRoot = \dirname(__DIR__, 3); // .../support

        // Case 1: developing this package as the root project
        $vendor = $packageRoot . DIRECTORY_SEPARATOR . 'vendor';
        if (is_dir($vendor)) {
            return $vendor;
        }

        // Case 2: this package is installed as a dependency: project/vendor/tamedevelopers/support/...
        $maybeProjectVendor = \dirname($packageRoot, 2); // .../project/vendor
        if (is_dir($maybeProjectVendor)) {
            return $maybeProjectVendor;
        }

        return null;
    }
}