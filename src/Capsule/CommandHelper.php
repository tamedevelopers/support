<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Capsule;

use Tamedevelopers\Support\Env;
use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Constant;
use Tamedevelopers\Support\Capsule\Logger;
use Tamedevelopers\Support\Process\HttpRequest;


/**
 * @property string $signature
 * @property string $description
 */
class CommandHelper
{
    /**
     * The database connector instance. 
     * @var \Tamedevelopers\Database\Connectors\Connector|null
     */
    protected $conn;

    /**
     * The database connector instance. 
     * @var bool|null
     */
    protected $isConsole;

    /**
     * Constructor  
     * @param \Tamedevelopers\Database\Connectors\Connector|null $conn
     */
    public function __construct($conn = null)
    {
        $dbInstance = "\Tamedevelopers\Database\DB";
        if(!is_null($conn) && $conn instanceof $dbInstance){
            $conn = $dbInstance::connection();
        }
        
        $this->conn = $conn;
        $this->isConsole = $this->isConsole();
    }
    
    /**
     * Checking for incoming CLI type
     * @param bool
     */
    protected function isConsole(): bool
    {
        return HttpRequest::runningInConsole();
    }
    
    /**
     * Alias for `isConsole()` method
     * @param bool
     */
    protected function runningInConsole(): bool
    {
        return $this->isConsole();
    }
    
    /**
     * Check if database connection is successful.
     * @param \Tamedevelopers\Database\Connectors\Connector $conn
     */
    protected function checkConnection($conn): void
    {
        $checkConnection = $conn->dbConnection();

        if($checkConnection['status'] != Constant::STATUS_200){
            if($this->isConsole()){
                $this->error($checkConnection['message']);
                exit(1);
            }
            return;
        }
    }

    /**
     * Determines if the application is running in a given environment.
     * 
     * @param array|string $env
     * @param bool $strict
     */
    protected function environment($env = 'local', $strict = false): bool
    {
        return Env::environment($env, $strict);
    }
    
    /**
     * Check if the command should be forced when running in production.
     */
    protected function forceChecker(): void
    {
        $args = $this->force();

        $force = (isset($args['force']) || isset($args['f']));

        if ($this->environment('production')) {
            if (!$force) {
                $this->error("You are in production! Use [--force|-f] flag, to run this command.");
                if($this->isConsole()){
                    exit(1);
                }
                return;
            }
        }
    }

    /**
     * Get force flag
     */
    protected function force(): bool
    {
        $args = $this->flags();

        if(isset($args['force'])){
            return $args['force'];
        }

        if(isset($args['f'])){
            return $args['f'];
        }

        return false;
    }
    
    /**
     * Extracts all arguments available from command
     * 
     * @param int|null $position
     * @return array
     */
    protected function arguments($position = null)
    {
        $args = $this->argument();

        return $args[$position] ?? $args;
    }
    
    /**
     * Extracts all arguments available from command
     * 
     * @param string|null $name
     * @return mixed
     */
    protected function argument($name = null)
    {
        $args = $this->argumentGrammer('arguments');

        return match ($name) {
            'name' => $args[0] ?? '',
            default => $args,
        };
    }
    
    /**
     * Extracts all flags available from command
     * 
     * @param string $key 
     * @return array
     */
    protected function flags()
    {
        return $this->options();
    }
    
    /**
     * Get a specific flag value from options array.
     * Example: option('path')
     * 
     * @param string $key 
     * @return mixed
     */
    protected function flag(string $key)
    {
        $args = $this->flags();

        return $args[$key] ?? null;
    }
    
    /**
     * Check if (flag) exists and is truthy.
     * 
     * @param string $key 
     * @return bool
     */
    protected function hasFlag($key)
    {
        $args = $this->flags();

        return in_array($key, array_keys($args));
    }

    /**
     * Extracts all options available from command
     */
    protected function options(): array
    {
        return $this->argumentGrammer('options');
    }

    /**
     * Get a specific option value from options array.
     * Example: option('force', false)
     * 
     * @param string $key 
     * @param mixed $default
     * 
     * @return mixed
     */
    protected function option(string $key, $default = null)
    {
        $args = $this->options();

        return $args[$key] ?? $default;
    }

    /**
     * Check if (option) exists and is truthy.
     */
    protected function hasOption(string $key): bool
    {
        $args = $this->options();

        return !empty($args[$key]);
    }

    /**
     * Get backtrace information about the caller's context
     *
     * @param string|null $key
     * @return array
     */
    protected function argumentGrammer($key = null)
    {
        $data = ['arguments' => [], 'options' => []];
        $traces = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 10);
        
        foreach ($traces as $frame) {
            $frameArgs = $frame['args'];

            if (empty($frameArgs) || !is_array($frameArgs)) {
                continue;
            }

            $isValidArgs = count($frameArgs) > 1 && is_object($frameArgs[0]) && is_string($frameArgs[1]);

            if($isValidArgs){
                $data['arguments']  = $frameArgs[2] ?? [];
                $data['options']    = $frameArgs[3] ?? [];
                break;
            }
        }

        return $data[$key] ?? $data;
    }

    /**
     * Get Command Signature
     */
    protected function signature(): string
    {
        return isset($this->signature) ? $this->signature : '';
    }

    /**
     * Get Command Description
     */
    protected function description(): string
    {
        return isset($this->description) ? $this->description : '';
    }

    /**
     * Get the signature name (the first token before any whitespace).
     * Examples:
     *  - 'make:service {name : ...}' => 'make:service'
     *  - 'name' => 'name'
     *  - 'name:value {opt}' => 'name:value'
     *  - 'name-first {arg}' => 'name-first'
     */
    protected function getSignatureName(): string
    {
        $sig = Str::trim($this->signature());
        if ($sig === '') {
            return '';
        }

        // Split on whitespace and take the first token
        $parts = preg_split('/\s+/', $sig, 2);
        $first = $parts[0] ?? '';

        // Trim surrounding quotes if any
        return trim($first, "\"'");
    }

    /**
     * Extract table name from a migration string.
     * Supports common patterns like:
     *  - create_users_table          => users
     *  - users_table                 => users
     * Fallback: returns a normalized name if no strict pattern matched.
     *
     * @param string|null $migration
     * @return string
     */
    protected function extractTableName($migration = null)
    {
        $name = Str::lower($migration);

        // 1) create_{table}_table
        if (preg_match('/^create_(.+)_table$/', $name, $matches)) {
            return $matches[1];
        }

        // 2) anything ending with _table
        if (preg_match('/^(.+)_table$/', $name, $matches)) {
            return $matches[1];
        }

        return $migration;
    }

    /**
     * Prompt the user for confirmation (y/n).
     */
    protected function confirm(string $question, bool $default = false): bool
    {
        $yesNo = $default ? 'y/n' : 'Y/N';
        
        while (true) {
            $answer = readline("{$question} ({$yesNo}): ");

            if (!empty($answer)) {
                $answer = Str::lower(trim($answer));

                if (in_array($answer, ['y', 'yes'], true)) {
                    return true;
                }

                if (in_array($answer, ['n', 'no'], true)) {
                    return false;
                }
            }
        }
    }

    /**
     * Prompt the user for free text input.
     */
    protected function ask(string $question, string $default = ''): string
    {
        // Print the question and force a new line
        echo $question . PHP_EOL . "> ";

        // Now capture user input
        $answer = trim(readline());

        return $answer !== '' ? $answer : $default;
    }

    /**
     * Display a simple progress bar.
     * This implementation writes directly to STDOUT using a carriage return (\r),
     * which updates the same line reliably in Windows CMD and Unix terminals.
     */
    protected function progressBar(callable $callback, int $total = 1, int $barWidth = 50): void
    {
        $completed = 0;

        // Writer compatible with CMD: use STDOUT + fflush, fallback to echo.
        $write = static function (string $text): void {
            if (defined('STDOUT')) {
                fwrite(STDOUT, $text);
                fflush(STDOUT);
            } else {
                echo $text;
            }
        };

        $draw = static function (int $completed, int $total, int $barWidth, callable $write): void {
            $safeTotal = max(1, $total);
            $percent   = (int) floor(($completed / $safeTotal) * 100);
            if ($percent > 100) {
                $percent = 100;
            }
            $filled  = (int) floor(($percent / 100) * $barWidth);
            $empty   = max(0, $barWidth - $filled);
            $write("\r[ " . str_repeat('#', $filled) . str_repeat('-', $empty) . " ] {$percent}%");
        };

        // Initial draw (0%)
        $draw(0, $total, $barWidth, $write);

        // $report closure to update the bar after each unit of work
        $report = function() use (&$completed, $total, $barWidth, $write, $draw) {
            $completed++;
            $draw($completed, $total, $barWidth, $write);
        };

        try {
            // execute the callback and pass the $report closure
            $callback($report);
        } finally {
            // Finish the line
            $write(PHP_EOL);
        }
    }

    /**
     * Write a header message.
     * @param string $header
     */
    protected function handleHeader($header): void
    {
        Logger::writeln('<yellow>Usage:</yellow>');
        Logger::writeln('  command [options] [arguments]');
        Logger::writeln('');
        Logger::writeln("<yellow>Available Commands for [{$header}] namespace:</yellow>");
    }

    /**
     * Write an info message.
     */
    protected function info(string $message): void
    {
        Logger::info($message . "\n");
    }

    /**
     * Write a success message.
     */
    protected function success(string $message): void
    {
        Logger::success($message . "\n");
    }

    /**
     * Write a warning message.
     */
    protected function warning(string $message): void
    {
        Logger::warning($message . "\n");
    }

    /**
     * Write an error message.
     */
    protected function error(string $message): void
    {
        Logger::error($message . "\n");
    }
    
}