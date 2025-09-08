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
     * Split a command string into tokens while respecting quotes.
     */
    private static function tokenizeCommand(string $input): array
    {
        $input = trim($input);
        if ($input === '') {
            return [];
        }

        $tokens  = [];
        // Match: "double-quoted" | 'single-quoted' | unquoted\-chunks
        $pattern = '/"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"|\'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\'|(\\S+)/';
        if (preg_match_all($pattern, $input, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                if (($m[1] ?? '') !== '') {
                    $tokens[] = stripcslashes($m[1]);
                } elseif (($m[2] ?? '') !== '') {
                    $tokens[] = stripcslashes($m[2]);
                } else {
                    $tokens[] = $m[3];
                }
            }
        }

        return $tokens;
    }

    /**
     * Discover providers by scanning project and vendor package composer.json files.
     * This works both when developing this package standalone and when it's used
     * inside a consumer project that requires other packages.
     */
    private function discoverExternal(): void
    {
        $vendorPath = $this->resolveVendorPath();
        if (!$vendorPath || !is_dir($vendorPath)) {
            return;
        }

        $composerFiles = [];

        // 1) Include root project's composer.json (may declare providers)
        $projectRoot = \dirname($vendorPath);
        $rootComposer = $projectRoot . DIRECTORY_SEPARATOR . 'composer.json';
        if (is_file($rootComposer)) {
            $composerFiles[] = $rootComposer;
        }

        // 2) Include all vendor package composer.json files: vendor/*/*/composer.json
        $packagesPattern = $vendorPath . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . 'composer.json';
        $found = glob($packagesPattern) ?: [];
        if (!empty($found)) {
            $composerFiles = array_merge($composerFiles, $found);
        }

        // De-duplicate paths just in case
        $composerFiles = array_values(array_unique($composerFiles));

        foreach ($composerFiles as $composerJson) {
            $json = File::get($composerJson);
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
                if (!is_string($fqcn)) {
                    continue;
                }

                // Rely on Composer autoload being present (vendor/autoload.php loaded by entry script)
                if (!class_exists($fqcn)) {
                    continue;
                }

                if (isset(self::$registeredProviders[$fqcn])) {
                    continue; // already registered in this process
                }

                try {
                    $provider = new $fqcn();
                    $didRegister = false;

                    // Preferred: provider explicitly implements our interface
                    if ($provider instanceof \Tamedevelopers\Support\Capsule\CommandProviderInterface) {
                        $provider->register($this);
                        $didRegister = true;
                    }
                    // Fallback: detect register() signature and call appropriately
                    elseif (method_exists($provider, 'register')) {
                        try {
                            $rm = new \ReflectionMethod($provider, 'register');
                            $paramCount = $rm->getNumberOfParameters();

                            if ($paramCount === 0) {
                                // Laravel-like providers often have register() with no args
                                $provider->register();
                                $didRegister = true;
                            } else {
                                // If first param accepts our Artisan (or is untyped/mixed/optional), pass it
                                $first = $rm->getParameters()[0];
                                $canPassArtisan = true;

                                if ($first->hasType()) {
                                    $t = $first->getType();
                                    if ($t instanceof \ReflectionNamedType) {
                                        $name = ltrim($t->getName(), '\\');
                                        $canPassArtisan = ($name === 'Tamedevelopers\\Support\\Capsule\\Artisan') || ($name === 'mixed') || ($name === 'array') || ($name === 'object');
                                    }
                                }

                                if ($canPassArtisan) {
                                    $provider->register($this);
                                    $didRegister = true;
                                } elseif ($first->isOptional()) {
                                    $provider->register();
                                    $didRegister = true;
                                }
                            }
                        } catch (\Throwable $ignored) {
                            // If reflection or calling fails, we just skip this provider
                        }
                    }

                    if ($didRegister) {
                        self::$registeredProviders[$fqcn] = true;
                    }
                } catch (\Throwable $e) {
                    // ignore provider instantiation/registration failures, but do not abort discovery
                }
            }
        }
    }

    /**
     * Resolve the vendor directory path robustly for both:
     * - Developing this package as the root project (support/vendor)
     * - When installed inside another project (project/vendor)
     */
    private function resolveVendorPath(): ?string
    {
        // Start from this file's directory and walk upwards to find:
        // - a 'vendor' directory sibling (e.g., support/vendor), or
        // - a parent directory named 'vendor' (e.g., project/vendor)
        $dir = __DIR__;

        for ($i = 0; $i < 8; $i++) {
            if ($dir === DIRECTORY_SEPARATOR || $dir === '/' || $dir === '') {
                break;
            }

            // Case A: current directory is the vendor directory
            if (\basename($dir) === 'vendor' && \is_dir($dir)) {
                return $dir;
            }

            // Case B: there is a vendor directory inside the current directory
            $candidate = $dir . DIRECTORY_SEPARATOR . 'vendor';
            if (\is_dir($candidate)) {
                return $candidate;
            }

            $dir = \dirname($dir);
        }

        return null;
    }
}