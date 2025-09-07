<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Capsule;

use Tamedevelopers\Support\Env;
use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Constant;
use Tamedevelopers\Support\Capsule\Logger;


class CommandHelper
{   
    /**
     * The database connector instance. 
     * @var \Tamedevelopers\Database\Connectors\Connector|null
     */
    protected $conn;

    /**
     * Constructor  
     * @param \Tamedevelopers\Database\Connectors\Connector|null $conn
     */
    public function __construct($conn = null)
    {
        $this->conn = $conn;
    }
    
    /**
     * Check if database connection is successful.
     * @param \Tamedevelopers\Database\Connectors\Connector $conn
     */
    protected function checkConnection($conn): void
    {
        $checkConnection = $conn->dbConnection();

        if($checkConnection['status'] != Constant::STATUS_200){
            $this->error($checkConnection['message']);
            exit();
        }
    }
    
    /**
     * Check if the command should be forced when running in production.
     */
    protected function forceChecker($options = []): void
    {
        $force = isset($options['force']) || isset($options['f']);

        if ($this->isProduction()) {
            if (!$force) {
                $this->error("You are in production! Use [--force|-f] flag, to run this command.");
                exit(1);
            }
        }
    }
    
    /**
     * Extracts the flag types from option keys like "drop-types" or "drop-views".
     */
    protected function flag($options = []): array
    {
        $types = [];
        foreach ($options as $key => $value) {
            if (strpos($key, 'drop-') === 0 && $value) {
                $types[] = substr($key, strlen('drop-')); // get the part after "drop-"
            }
        }
        return $types;
    }

    /**
     * Determine if the current environment is production.
     */
    protected function isProduction(): bool
    {
        $env = Env::env('APP_ENV');
        $productionAliases = ['prod', 'production', 'live'];

        return in_array(Str::lower($env), $productionAliases, true);
    }

    /**
     * Get a specific option value from options array.
     * Example: option('force', false)
     * 
     * @param string $key 
     * @param string|array|bool|null $default
     * 
     * @return mixed
     */
    protected function option(string $key, $default = null)
    {
        // backtrace
        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);

        // get backtrace information about the caller's context
        $args = $trace[1]['args'][1] ?? [];

        return $args[$key] ?? $default;
    }

    /**
     * Check if an option/flag exists and is truthy.
     */
    protected function hasOption(string $key): bool
    {
        // backtrace
        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);

        // get backtrace information about the caller's context
        $args = $trace[1]['args'][1] ?? [];

        return !empty($args[$key]);
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
        $yesNo  = $default ? 'Y/n' : 'y/N';
        $answer = readline("{$question} [{$yesNo}]: ");

        if (empty($answer)) {
            return $default;
        }

        return in_array(Str::lower($answer), ['y', 'yes'], true);
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
        Logger::writeln("<warning>{$message}</warning>");
    }

    /**
     * Write an error message.
     */
    protected function error(string $message): void
    {
        Logger::error($message . "\n");
    }
    
}