<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Capsule;

use Tamedevelopers\Support\Capsule\Logger;
use Tamedevelopers\Support\Capsule\Manager;
use Tamedevelopers\Support\Capsule\Traits\ArtisanTrait;
use Tamedevelopers\Support\Capsule\Traits\ArtisanDiscovery;

/**
 * Minimal artisan-like dispatcher for Tamedevelopers Support
 *
 * Supports Laravel-like syntax:
 *   php tame <command>[:subcommand] [--flag] [--option=value]
 * Examples:
 *   php tame key:generate
 *   php tame migrate:fresh --seed --database=mysql
 */
class Artisan
{
    use ArtisanTrait, 
        ArtisanDiscovery;

    /**
     * Command registry
     * @var string
     */
    public $cliname;

    /**
     * Registered commands map
     * @var array<string, array{instance?: object, handler?: callable, description: string}>
     */
    protected static array $commands = [];

    /**
     * Guard to ensure discovery runs only once per process.
     */
    private static bool $discovered = false;

    public function __construct()
    {
        // Ensure environment variables are loaded before accessing them
        Manager::startEnvIFNotStarted();
        
        // Auto-discover external commands from installed packages
        $this->discoverExternal();
    }

    /**
     * Register a command by name with description
     *
     * @param string $name 
     * @param callable|object $handler  Either a callable or a command class instance
     * @param string $description       Short description for `list`
     */
    public function register(string $name, $handler, string $description = ''): void
    {
        if (\is_object($handler) && !\is_callable($handler)) {
            self::$commands[$name] = [
                'instance' => $handler,
                'description' => $description,
            ];
            return;
        }

        // Fallback to callable handler registration
        self::$commands[$name] = [
            'handler' => $handler,
            'description' => $description,
        ];
    }

    /**
     * Handle argv input and dispatch
     */
    public function run(array $argv): int
    {
        // In PHP CLI, $argv[0] is the script name (tame), so command starts at index 1
        $commandInput = $argv[1] ?? 'list';
        $rawArgs = array_slice($argv, 2);

        // Ensure external commands are discovered even if constructed elsewhere
        $this->discoverExternal();

        if ($commandInput === 'list') {
            $this->renderList();
            return 0;
        }

        // Parse base and optional subcommand: e.g., key:generate -> [key, generate]
        [$base, $sub] = $this->splitCommand($commandInput);

        if (!isset(self::$commands[$base])) {
            Logger::error("Command \"{$commandInput}\" is not defined.\n\n");
            return 1;
        }

        $entry = self::$commands[$base];

        // Parse flags/options once and pass where applicable
        [$positionals, $options] = $this->parseArgs($rawArgs);

        // If registered with a class instance, we support subcommands and flag-to-method routing
        if (isset($entry['instance']) && \is_object($entry['instance'])) {
            $instance = $entry['instance'];

            // Resolve primary method to call
            $primaryMethod = $sub ?: 'handle';
            if (!method_exists($instance, $primaryMethod)) {
                // command instance name
                $instanceName = get_class($instance);
                Logger::error("Missing method \"{$primaryMethod}()\" in {$instanceName}.");

                // Show small hint for available methods on the instance (public only)
                $hints = $this->introspectPublicMethods($instance);

                if ( !empty($hints)) {
                    Logger::info("Available methods: {$hints}\n");
                } 
                return 1;
            }

            $exitCode = (int) ($this->invokeCommandMethod($instance, $primaryMethod, $positionals, $options) ?? 0);

            // Route flags as methods on the same instance
            $invalidFlags = [];
            foreach ($options as $flag => $value) {
                $method = $this->optionToMethodName($flag);
                // Skip if this flag matches the already-run primary method
                if ($method === $primaryMethod) {
                    continue;
                }
                if (method_exists($instance, $method)) {
                    $this->invokeCommandMethod($instance, $method, $positionals, $options, $flag);
                } else {
                    $invalidFlags[] = $flag;
                }
            }

            if (!empty($invalidFlags)) {
                Logger::error("Invalid option/method: --" . implode(', --', $invalidFlags) . "\n");
            }

            return $exitCode;
        }

        // Fallback: callable handler (no subcommands/flags routing)
        if (isset($entry['handler']) && \is_callable($entry['handler'])) {
            $handler = $entry['handler'];
            return (int) ($handler($rawArgs) ?? 0);
        }

        Logger::error("Command not properly registered: {$commandInput}\n");
        return 1;
    }

    /**
     * Render list of available commands
     */
    private function renderList(): void
    {
        $this->cliname = "{$this->cliname}\n" ?: "Tamedevelopers Support CLI\n";

        Logger::helpHeader($this->cliname);
        Logger::writeln('<yellow>Usage:</yellow>');
        Logger::writeln('  php tame <command> [:option] [arguments]');
        Logger::writeln('');

        $grouped = $this->buildGroupedCommandList(self::$commands);
        
        // Root commands first
        if (isset($grouped['__root'])) {
            Logger::helpHeader('Available commands:', 'method');
            foreach ($grouped['__root'] as $cmd => $desc) {
                // Label with color
                $label = Logger::segments([
                    ['text' => $cmd, 'style' => 'green'],
                ]);

                // Pad description to align
                $visibleLen = strlen(preg_replace('/<\/?[a-zA-Z0-9_-]+>/', '', '  ' . $label) ?? '');
                $spaces = max(1, 35 - $visibleLen);

                Logger::writeln('  ' . $label . str_repeat(' ', $spaces) . Logger::segments([
                    ['text' => $desc, 'style' => 'desc'],
                ]));
            }
            Logger::writeln('');
            unset($grouped['__root']);
        }

        // Then grouped by base (e.g., auth, cache, migrate:*)
        foreach ($grouped as $group => $items) {
            Logger::helpHeader($group, 'method');
            foreach ($items as $full => $desc) {
                [$ns, $method] = explode(':', $full, 2);
                // method (yellow), description (white)
                Logger::helpItem($ns, $method, null, $desc, 35, false, ['green', 'green']);
            }
            Logger::writeln('');
        }
    }

}