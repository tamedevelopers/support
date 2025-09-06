<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Capsule\Traits;

/**
 * Provides helpers to build an associative array of available commands
 * from the Artisan registry, including subcommand methods on command classes.
 */
trait ArtisanTrait
{
    /**
     * Build a grouped list keyed by the base command name.
     * Root commands without a colon are under key '__root'.
     *
     * @param array<string, array<int, array{instance?: object, handler?: callable, description: string}>> $registry
     * @return array<string, array<string,string>>
     */
    protected function buildGroupedCommandList(array $registry): array
    {
        $flat = $this->buildCommandList($registry);
        $grouped = [];
        foreach ($flat as $cmd => $desc) {
            $pos = strpos($cmd, ':');
            if ($pos === false) {
                // Merge root entries with same name; prefer non-empty description
                $existing = $grouped['__root'][$cmd] ?? '';
                $grouped['__root'][$cmd] = $existing !== '' ? $existing : $desc;
            } else {
                $group = substr($cmd, 0, $pos);
                $grouped[$group][$cmd] = $desc;
            }
        }
        // Sort groups and each group's items
        ksort($grouped, SORT_NATURAL | SORT_FLAG_CASE);
        foreach ($grouped as &$items) {
            ksort($items, SORT_NATURAL | SORT_FLAG_CASE);
        }
        return $grouped;
    }

    /**
     * Build a flat associative list of commands => description.
     * Includes base commands and public subcommand methods (e.g., foo:bar).
     *
     * @param array<string, array<int, array{instance?: object, handler?: callable, description: string}>> $registry
     * @return array<string,string>
     */
    private function buildCommandList(array $registry): array
    {
        $list = [];

        foreach ($registry as $name => $entries) {
            // Normalize entries to array of providers (list if index 0 exists)
            $providers = is_array($entries) && isset($entries[0]) ? $entries : [$entries];

            $rootDesc = '';
            foreach ($providers as $entry) {
                $desc = (string)($entry['description'] ?? '');
                if ($rootDesc === '' && $desc !== '') {
                    $rootDesc = $desc; // prefer first non-empty description
                }

                if (isset($entry['instance']) && is_object($entry['instance'])) {
                    $instance = $entry['instance'];
                    foreach ($this->introspectPublicMethodsArray($instance) as $method => $summary) {
                        if ($method === 'handle') {
                            continue;
                        }
                        // If subcommand already exists, prefer a non-empty summary
                        $key = $name . ':' . $method;
                        if (!isset($list[$key]) || $list[$key] === '') {
                            $list[$key] = $summary;
                        }
                    }
                }
            }

            // Root command entry
            if (!isset($list[$name])) {
                $list[$name] = $rootDesc;
            } else {
                // Prefer non-empty description if existing was empty
                if ($list[$name] === '' && $rootDesc !== '') {
                    $list[$name] = $rootDesc;
                }
            }
        }

        ksort($list, SORT_NATURAL | SORT_FLAG_CASE);
        return $list;
    }


    /**
     * Introspect public methods and return [methodName => summary] map.
     * Summary is derived from the method's PHPDoc first line when available.
     *
     * @return array<string,string>
     */
    private function introspectPublicMethodsArray(object $instance): array
    {
        $out = [];
        try {
            $ref = new \ReflectionClass($instance);
            foreach ($ref->getMethods(\ReflectionMethod::IS_PUBLIC) as $m) {
                $name = $m->getName();
                if ($name === '__construct' || str_starts_with($name, '__')) {
                    continue;
                }
                $summary = $this->extractDocSummary($m->getDocComment() ?: '') ?: '';
                $out[$name] = $summary;
            }
        } catch (\Throwable $e) {
            // ignore
        }
        ksort($out, SORT_NATURAL | SORT_FLAG_CASE);
        return $out;
    }

    /**
     * Extract the first non-empty line from a PHPDoc block as summary.
     */
    private function extractDocSummary(string $doc): string
    {
        if ($doc === '') {
            return '';
        }
        $doc = preg_replace('/^\s*\/\*\*|\*\/\s*$/', '', $doc ?? '');
        $lines = preg_split('/\r?\n/', (string)$doc) ?: [];
        foreach ($lines as $line) {
            $line = trim(preg_replace('/^\s*\*\s?/', '', $line ?? ''));
            if ($line !== '' && strpos($line, '@') !== 0) {
                return $line;
            }
        }
        return '';
    }

    /**
     * Split command into base and optional subcommand
     * @return array{0:string,1:?string}
     */
    private function splitCommand(string $input): array
    {
        $parts = explode(':', $input, 2);
        $base = $parts[0] ?? '';
        $sub = $parts[1] ?? null;
        return [$base, $sub];
    }

    /**
     * Parse raw args into positionals and options/flags.
     * Options like: --name=value, --name value, --flag
     * Short flags like -abc will be split into a,b,c set to true
     *
     * @param array $args
     * @return array{0:array,1:array<string,mixed>}
     */
    private function parseArgs(array $args): array
    {
        $positionals = [];
        $options = [];

        for ($i = 0; $i < count($args); $i++) {
            $arg = $args[$i];

            if (str_starts_with($arg, '--')) {
                $eqPos = strpos($arg, '=');
                if ($eqPos !== false) {
                    $key = substr($arg, 2, $eqPos - 2);
                    $val = substr($arg, $eqPos + 1);
                    $options[$key] = $val;
                } else {
                    $key = substr($arg, 2);
                    // If next token exists and is not an option, treat as value
                    $next = $args[$i + 1] ?? null;
                    if ($next !== null && !str_starts_with((string)$next, '-')) {
                        $options[$key] = $next;
                        $i++; // consume next
                    } else {
                        $options[$key] = true;
                    }
                }
            } elseif (str_starts_with($arg, '-')) {
                // Short flags cluster: -abc => a=true, b=true, c=true
                $cluster = substr($arg, 1);
                foreach (str_split($cluster) as $ch) {
                    $options[$ch] = true;
                }
            } else {
                $positionals[] = $arg;
            }
        }

        return [$positionals, $options];
    }

    /**
     * Convert an option name (e.g., "seed" or "database") to a method name (foo-bar => fooBar)
     */
    private function optionToMethodName(string $option): string
    {
        $name = ltrim($option, '-');
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '', $name ?? '');
        $parts = preg_split('/[-_]/', (string)$name) ?: [];
        $camel = '';
        foreach ($parts as $idx => $p) {
            $p = strtolower($p);
            $camel .= $idx === 0 ? $p : ucfirst($p);
        }
        return $camel ?: 'handle';
    }

    /**
     * Invoke a command method with flexible signature support.
     * Method may declare: fn():int, fn(array $args):int, fn(array $args, array $options):int
     */
    private function invokeCommandMethod(object $instance, string $method, array $args, array $options, ?string $invokedByFlag = null)
    {
        try {
            $ref = new \ReflectionMethod($instance, $method);
            $paramCount = $ref->getNumberOfParameters();
            if ($paramCount >= 2) {
                return $ref->invoke($instance, $args, $options);
            }
            if ($paramCount === 1) {
                return $ref->invoke($instance, $args);
            }
            return $ref->invoke($instance);
        } catch (\Throwable $e) {
            $flagInfo = $invokedByFlag ? " (from --{$invokedByFlag})" : '';
            fwrite(STDERR, "Error running {$method}{$flagInfo}: {$e->getMessage()}\n");
            return 1;
        }
    }

    /**
     * Introspect public methods (excluding magic/constructor) for hints
     */
    private function introspectPublicMethods(object $instance): string
    {
        try {
            $ref = new \ReflectionClass($instance);
            $methods = [];
            foreach ($ref->getMethods(\ReflectionMethod::IS_PUBLIC) as $m) {
                $name = $m->getName();
                if ($name === '__construct' || str_starts_with($name, '__')) {
                    continue;
                }
                $methods[] = $name;
            }
            sort($methods);
            return implode(', ', $methods);
        } catch (\Throwable $e) {
            return '';
        }
    }
    
}