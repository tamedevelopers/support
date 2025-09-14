<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Capsule;

use Tamedevelopers\Support\Capsule\Logger;
use Tamedevelopers\Support\Capsule\Manager;
use Tamedevelopers\Support\Capsule\CommandHelper;
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
class Artisan extends CommandHelper
{
    use ArtisanTrait, 
        ArtisanDiscovery;

    /**
     * Command registry
     * @var string
     */
    public $cli_name;

    /**
     * Registered commands map (supports multiple providers per base command)
     * @var array<string, array<int, array{instance?: object, handler?: callable, description: string}>>
     */
    protected static array $commands = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        // Ensure environment variables are loaded before accessing them
        Manager::startEnvIFNotStarted();

        // Auto-discover external commands from installed packages
        $this->discoverExternal();
    }

    /**
     * Programmatically execute a CLI command string.
     *
     * Example:
     *   Artisan::call("make:command UserCommand --path=users");
     *
     * Notes:
     * - Quoted args are respected: "--path=\"my path\""
     * - Placeholder tokens like [name] are ignored if passed literally.
     */
    public static function call(string $input)
    {
        // Tokenize input and drop placeholder tokens like [name]
        $tokens = self::tokenizeCommand($input);
        $tokens = array_values(array_filter($tokens, static function ($t) {
            return !preg_match('/^\[[^\]]+\]$/', (string)$t);
        }));

        // Build argv in the same format as PHP CLI provides
        $command = $tokens[0] ?? 'list';
        $args    = array_slice($tokens, 1);
        $argv    = array_merge(['tame'], [$command], $args);

        // Instantiate dispatcher and ensure default/internal commands are registered
        $artisan = new self();

        $makeCmd = '\Tamedevelopers\Support\Commands\MakeCommand';
        $processorCmd = '\Tamedevelopers\Support\Commands\ProcessorCommand';

        // Register built-in commands that are normally wired in the CLI entrypoint (idempotent)
        if (class_exists($makeCmd)) {
            if (!isset(self::$commands['make'])) {
                $makeCmd = new $makeCmd();
                [$signature, $description] = [
                    $makeCmd->getSignatureName(), $makeCmd->description(),
                ];
                $artisan->register($signature, $makeCmd, $description);
            }
        }
        if (class_exists($processorCmd)) {
            if (!isset(self::$commands['processor'])) {
                $processorCmd = new $processorCmd();
                [$signature, $description] = [
                    $processorCmd->getSignatureName(), $processorCmd->description(),
                ];
                $artisan->register($signature, $processorCmd, $description);
            }
        }

        // register and discover other commands from other packages
        $artisan->discoverExternal();

        return $artisan->run($argv);
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
        // Ensure bucket exists for this base name
        if (!isset(self::$commands[$name]) || !is_array(self::$commands[$name])) {
            self::$commands[$name] = [];
        }

        // Object instance (class-based command)
        if (\is_object($handler) && !\is_callable($handler)) {
            self::$commands[$name][] = [
                'instance' => $handler,
                'description' => $description,
            ];
            return;
        }

        // Callable handler registration
        self::$commands[$name][] = [
            'handler' => $handler,
            'description' => $description,
        ];
    }

    /**
     * Register multiple commands
     * 
     * @param array $commands
     * - Accepts 
     * Command Class
     */
    public function registerAll(array $commands): void
    {
        // Otherwise, treat as a list of command definitions
        foreach ($commands as $command) {
            [$signature, $description] = [
                $command->getSignatureName(), $command->description(),
            ];

            // if class doesn't exists then ignore and continue
            if(!class_exists($command::class)){
                continue;
            }

            // if class doesn't have signature then ignore and continue
            if(empty($signature)){
                continue;
            }

            $this->register($signature, $command, $description);
        }
    }

    /**
     * Handle argv input and dispatch
     *
     * When running in console, returns an int exit code.
     * When running from web (non-console), returns the command's result if available; otherwise, the exit code.
     */
    public function run(array $argv)
    {
        // In PHP CLI, $argv[0] is the script name (tame), so command starts at index 1
        $commandInput = $argv[1] ?? 'list';
        $rawArgs = array_slice($argv, 2); 

        // Ensure external commands are discovered even if constructed elsewhere
        $this->discoverExternal();

        if ($commandInput === 'list') {
            $this->renderList();
            // For web context, nothing to return; keep consistent exit code 0
            return 0;
        }

        // Parse base and optional subcommand: e.g., key:generate -> [key, generate]
        [$base, $sub] = $this->splitCommand($commandInput);

        if (!isset(self::$commands[$base])) {
            Logger::error("Command \"{$commandInput}\" is not defined.\n\n");
            return 0;
        }

        // Normalize entries to array of providers for this base command
        $entries = self::$commands[$base];
        // Normalize: if entries look like a single assoc, wrap into list
        if (!is_array($entries) || (is_array($entries) && !isset($entries[0]))) {
            $entries = [$entries];
        }

        // Parse flags/options once and pass where applicable
        [$positionals, $options] = $this->parseArgs($rawArgs);

        $exitCode = 0;
        $handled = false;
        $firstResult = null; // capture first non-null non-int result

        // Resolve primary once and track unresolved flags across providers
        $primaryMethod = $sub ?: 'handle';
        $unresolvedFlags = array_keys($options);
        
        foreach ($entries as $entry) {
            // If registered with a class instance, support subcommands and flag-to-method routing
            if (isset($entry['instance']) && \is_object($entry['instance'])) {
                $instance = $entry['instance'];

                // Skip instances that don't implement the requested primary method
                if (!method_exists($instance, $primaryMethod)) {
                    continue;
                }

                $raw = $this->invokeCommandMethod($instance, $primaryMethod, $positionals, $options);
                $exitCode = max($exitCode, is_numeric($raw) ? (int)$raw : 0);
                if ($firstResult === null && $raw !== null && !is_int($raw)) {
                    $firstResult = $raw;
                }
                $handled = true;

                // Route flags as methods on the same instance and mark them as resolved
                foreach ($unresolvedFlags as $i => $flag) {
                    $method = $this->optionToMethodName($flag);
                    if ($method === $primaryMethod) {
                        unset($unresolvedFlags[$i]);
                        continue;
                    }
                    if (method_exists($instance, $method)) {
                        $this->invokeCommandMethod($instance, $method, $positionals, $options, $flag);
                        unset($unresolvedFlags[$i]);
                    }
                }

                continue;
            }

            // Fallback: callable handler (no subcommands/flags routing)
            if (isset($entry['handler']) && \is_callable($entry['handler'])) {
                $handler = $entry['handler'];
                $raw = $handler($rawArgs);
                $exitCode = max($exitCode, is_numeric($raw) ? (int)$raw : 0);
                if ($firstResult === null && $raw !== null && !is_int($raw)) {
                    $firstResult = $raw;
                }
                $handled = true;
                continue;
            }

            // Unknown provider shape; ignore but keep iterating
        }

        if (!$handled) {
            if ($sub !== null) {
                Logger::error("Command \"{$commandInput}\" is not defined.\n\n");
            } else {
                Logger::error("No valid providers handled command: {$commandInput}\n");
            }
            return max($exitCode, 1);
        }
        
        // prefer returning the first meaningful result or exit code
        return $firstResult !== null ? $firstResult : $exitCode;
    }

    /**
     * Render list of available commands
     */
    private function renderList(): void
    {
        $this->cli_name = !empty($this->cli_name)
                        ? "{$this->cli_name}\n" 
                        : "Tamedevelopers Support CLI\n";
        
        Logger::helpHeader($this->cli_name);
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