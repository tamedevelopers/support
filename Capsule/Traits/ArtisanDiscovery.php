<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Capsule\Traits;

use Tamedevelopers\Support\Capsule\File;


/**
 * @property static $discovered
 */
trait ArtisanDiscovery
{
    
    /**
     * Discover providers from installed Composer packages.
     * Convention:
     * - extra.tamedevelopers.providers: string[] FQCNs implementing CommandProviderInterface
     */
    private function discoverExternal(): void
    {
        if (self::$discovered) {
            return;
        }
        self::$discovered = true;

        $installedPath = $this->resolveInstalledJsonPath();
        if (!$installedPath || !is_file($installedPath)) {
            return;
        }

        $json = File::get($installedPath);
        if ($json === false) {
            return;
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            return;
        }

        $packages = $this->extractPackages($data);

        foreach ($packages as $pkg) {
            $extra = $pkg['extra']['tamedevelopers'] ?? null;
            if (!$extra) {
                continue;
            }

            // 1) Providers
            $providers = $extra['providers'] ?? [];
            foreach ((array) $providers as $fqcn) {
                if (\is_string($fqcn) && \class_exists($fqcn)) {
                    try {
                        $provider = new $fqcn();
                        if (\method_exists($provider, 'register')) {
                            $provider->register($this);
                        }
                    } catch (\Throwable $e) {
                        // skip provider instantiation errors silently to avoid breaking CLI
                    }
                }
            }
        }
    }

    /**
     * Handle different shapes of installed.json across Composer versions.
     */
    private function extractPackages(array $data): array
    {
        // Composer 2: {"packages":[...]} or multi-vendor arrays
        if (isset($data['packages']) && is_array($data['packages'])) {
            return $data['packages'];
        }
        if (isset($data[0]['packages'])) {
            $merged = [];
            foreach ($data as $block) {
                if (isset($block['packages']) && is_array($block['packages'])) {
                    $merged = array_merge($merged, $block['packages']);
                }
            }
            return $merged;
        }

        // Some vendors put flat arrays
        if (isset($data['versions']) && is_array($data['versions'])) {
            $out = [];
            foreach ($data['versions'] as $name => $info) {
                if (is_array($info)) {
                    $info['name'] = $name;
                    $out[] = $info;
                }
            }
            return $out;
        }

        // Fallback: maybe already an array of packages
        return is_array($data) ? $data : [];
    }
    
    /**
     * Find vendor/composer/installed.json reliably relative to this package.
     */
    private function resolveInstalledJsonPath(): ?string
    {
        // This file is .../Tamedevelopers/Support/Capsule/Artisan.php inside a project root.
        // We want the consumer application's vendor/composer/installed.json.
        $projectRoot = \dirname(__DIR__, 2); // .../Tamedevelopers/Support
        $vendorPath  = $projectRoot . DIRECTORY_SEPARATOR . 'vendor';
        if (!is_dir($vendorPath)) {
            // Fallback for when this file is inside vendor/tamedevelopers/support
            $supportRoot = \dirname(__DIR__, 1);     // .../support (current package root)
            $vendorRoot  = \dirname($supportRoot, 2); // .../vendor
            $vendorPath  = $vendorRoot;
        }
        return $vendorPath . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'installed.json';
    }

}
