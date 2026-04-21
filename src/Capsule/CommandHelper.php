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
    protected function ask(string $question, string $default = null): string
    {
        $question = $this->ensureQuestionMark($question);

        // Print the question and force a new line
        echo $question . PHP_EOL . "> ";

        // Now capture user input
        $answer = trim(readline());

        return $answer !== '' ? $answer : $default;
    }

    /**
     * Prompt the user to choose from a list of values.
     *
     * Supports both indexed and associative arrays:
     * - ['mail', 'tame']
     * - ['mail' => 'Mail config', 'tame' => 'Tame config']
     *
     * @param string $question
     * @param mixed $values
     * @param mixed $default
     * @return mixed
     */
    protected function choice(string $question, $values, $default = null)
    {
        $question = $this->ensureQuestionMark($question);
        $options = $this->normalizeChoiceOptions($values);
        if (empty($options)) {
            return $default ?? '';
        }

        $defaultIndex = $this->resolveChoiceDefaultIndex($options, $default);

        if ($this->canUseInteractiveChoice()) {
            return $this->interactiveChoice($question, $options, $defaultIndex);
        }

        return $this->fallbackChoice($question, $options, $defaultIndex);
    }

    /**
     * Resolve the default option index.
     *
     * @param array $options
     * @param mixed $defaultValue
     * @return int
     */
    private function resolveChoiceDefaultIndex(array $options, $defaultValue): int
    {
        if ($defaultValue === null || $defaultValue === '') {
            return 0;
        }

        foreach ($options as $index => $option) {
            if (
                (string) $option['result'] === (string) $defaultValue ||
                (string) $option['key'] === (string) $defaultValue ||
                Str::lower((string) $option['label']) === Str::lower((string) $defaultValue)
            ) {
                return $index;
            }
        }

        return 0;
    }

    /**
     * Check if terminal can handle raw interactive key reading.
     *
     * @return bool
     */
    private function canUseInteractiveChoice(): bool
    {
        if (!$this->runningInConsole() || !defined('STDIN')) {
            return false;
        }

        // PowerShell/Windows terminals can intercept arrows and make raw input freeze.
        // Use fallback readline mode on Windows for reliable text/number/default selection.
        if (DIRECTORY_SEPARATOR === '\\') {
            return false;
        }

        return function_exists('shell_exec');
    }

    /**
     * Render a simple arrow-key interactive menu and return selected option.
     *
     * @param string $question
     * @param array $options
     * @param int $selectedIndex
     * @return mixed
     */
    private function interactiveChoice(string $question, array $options, int $selectedIndex)
    {
        $total = count($options);
        $maxLines = $total + 1;
        $terminalWidth = $this->resolveTerminalWidth();
        $write = static function (string $text): void {
            if (defined('STDOUT')) {
                fwrite(STDOUT, $text);
                fflush(STDOUT);
            } else {
                echo $text;
            }
        };

        $draw = function () use ($question, $options, &$selectedIndex, $write, $terminalWidth): void {
            $write($question . PHP_EOL);
            foreach ($options as $i => $option) {
                $marker = $i === $selectedIndex ? '>' : ' ';
                $row = $this->renderChoiceRow((string) $option['label'], (string) $option['key'], $terminalWidth - 3);
                $write(" {$marker} {$row}" . PHP_EOL);
            }
        };

        $restoreMode = static function (): void {
            if (DIRECTORY_SEPARATOR !== '\\') {
                @shell_exec('stty sane');
            }
        };

        try {
            if (DIRECTORY_SEPARATOR !== '\\') {
                @shell_exec('stty -icanon -echo');
            }

            $draw();
            while (true) {
                $key = $this->readChoiceKey();
                if ($key === '') {
                    continue;
                }

                if ($key === 'up') {
                    $selectedIndex = ($selectedIndex - 1 + $total) % $total;
                } elseif ($key === 'down') {
                    $selectedIndex = ($selectedIndex + 1) % $total;
                } elseif ($key === 'enter') {
                    return $options[$selectedIndex]['result'];
                } elseif (strlen($key) === 1 && ctype_alnum($key)) {
                    foreach ($options as $option) {
                        if ((string) $option['key'] === (string) $key) {
                            return $option['result'];
                        }
                    }
                } else {
                    // Unknown key: ignore and keep waiting for a valid key.
                    continue;
                }

                // Repaint menu in place.
                $write("\033[" . $maxLines . "A");
                for ($i = 0; $i < $maxLines; $i++) {
                    $write("\033[2K");
                    if ($i < $maxLines - 1) {
                        $write("\033[1B");
                    }
                }
                $write("\033[" . ($maxLines - 1) . "A");
                $draw();
            }
        } finally {
            $restoreMode();
        }
    }

    /**
     * Non-interactive choice fallback using a simple prompt.
     *
     * @param string $question
     * @param array $options
     * @param int $defaultIndex
     * @return mixed
     */
    private function fallbackChoice(string $question, array $options, int $defaultIndex)
    {
        $terminalWidth = $this->resolveTerminalWidth();

        echo $question . PHP_EOL;
        foreach ($options as $i => $option) {
            $marker = $i === $defaultIndex ? '*' : ' ';
            $row = $this->renderChoiceRow((string) $option['label'], (string) $option['key'], $terminalWidth - 3);
            echo " {$marker} {$row}" . PHP_EOL;
        }

        $answer = trim(readline("> "));
        if ($answer === '') {
            return $options[$defaultIndex]['result'];
        }

        foreach ($options as $option) {
            if (
                (string) $option['key'] === $answer ||
                Str::lower((string) $option['label']) === Str::lower($answer)
            ) {
                return $option['result'];
            }
        }

        if (ctype_digit($answer)) {
            $index = (int) $answer;
            return $options[$index]['result'] ?? $options[$defaultIndex]['result'];
        }

        return $options[$defaultIndex]['result'];
    }

    /**
     * Normalize choice values into display rows.
     *
     * @param mixed $values
     * @return array<int, array{label:string,key:string,result:mixed}>
     */
    private function normalizeChoiceOptions($values): array
    {
        if (!is_array($values)) {
            $values = [$values];
        }

        $rows = [];
        $isAssoc = $this->isAssocArray($values);

        if ($isAssoc) {
            foreach ($values as $key => $value) {
                $rows[] = [
                    'label' => (string) $value,
                    'key' => (string) $key,
                    'result' => $value,
                ];
            }

            return $rows;
        }

        foreach ($values as $index => $value) {
            $rows[] = [
                'label' => (string) $value,
                'key' => (string) $index,
                'result' => $value,
            ];
        }

        return $rows;
    }

    /**
     * Determine whether array is associative.
     *
     * @param array $array
     * @return bool
     */
    private function isAssocArray(array $array): bool
    {
        if ($array === []) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Render a dotted row: left label ........ right key.
     *
     * @param string $label
     * @param string $key
     * @return string
     */
    private function renderChoiceRow(string $label, string $key, ?int $lineWidth = null): string
    {
        $lineWidth = $lineWidth ?? $this->resolveTerminalWidth();
        $dotsCount = $lineWidth - strlen($label) - strlen($key) - 2;
        $dotsCount = max(4, $dotsCount);

        return $label . ' ' . str_repeat('.', $dotsCount) . ' ' . $key;
    }

    /**
     * Read a single choice key from terminal.
     * Returns: up|down|enter|single-char|''.
     *
     * @return string
     */
    private function readChoiceKey(): string
    {
        $char = fread(STDIN, 1);
        if ($char === false || $char === '') {
            return '';
        }

        if ($char === "\033") {
            $next = fread(STDIN, 1);
            $third = fread(STDIN, 1);
            if ($next === '[' && $third === 'A') {
                return 'up';
            }
            if ($next === '[' && $third === 'B') {
                return 'down';
            }
            return '';
        }

        if ($char === "\r" || $char === "\n") {
            return 'enter';
        }

        return $char;
    }

    /**
     * Resolve current terminal width with safe fallbacks.
     *
     * @return int
     */
    private function resolveTerminalWidth(): int
    {
        $width = (int) ($_SERVER['COLUMNS'] ?? 0);
        if ($width > 20) {
            return $width;
        }

        if (DIRECTORY_SEPARATOR === '\\' && function_exists('shell_exec')) {
            $out = (string) @shell_exec('powershell -NoProfile -NonInteractive -Command "$Host.UI.RawUI.WindowSize.Width"');
            $width = (int) trim($out);
            if ($width > 20) {
                return $width;
            }
        }

        if (DIRECTORY_SEPARATOR !== '\\' && function_exists('shell_exec')) {
            $out = (string) @shell_exec('tput cols 2>/dev/null');
            $width = (int) trim($out);
            if ($width > 20) {
                return $width;
            }
        }

        return 80;
    }


    /**
     * Check if question contains ? and add if missing
     *
     * @param string $question
     * @return string
     */
    private function ensureQuestionMark($question) {
        if (substr($question, -1) !== '?') {
            $question .= '?';
        }

        return $question;
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