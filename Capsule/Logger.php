<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Capsule;

use Tamedevelopers\Support\Str;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

/**
 * Class Logger
 *
 * Console logger with styled output and helpers for
 * segment coloring, padded descriptions, and STDERR output.
 *
 * Quick usage:
 *   Logger::info("Publishing files...");
 *   Logger::success("File published successfully.");
 *   Logger::error("Failed to publish file.");
 *
 * Advanced coloring:
 *   Logger::writeln(Logger::segments([
 *       ['text' => 'register($name)', 'style' => 'method'],
 *       ['text' => ' '],
 *       ['text' => 'key:force', 'style' => 'key'],
 *       ['text' => '  Some description'],
 *   ]));
 *
 * Help-like layout:
 *   Logger::helpHeader('blog-store');
 *   Logger::helpItem('blog-store', 'activities', null, 'Description', 30, false, ['green', 'yellow']);
 *
 * @package Tamedevelopers\Database\Capsule
 */
class Logger
{
    /**
     * Console output instance
     *
     * @var ConsoleOutput|null
     */
    protected static ?ConsoleOutput $output = null;

    /**
     * Get or create the ConsoleOutput with styles
     */
    protected static function getOutput(): ConsoleOutput
    {
        if (!static::$output) {
            $output = new ConsoleOutput();
            self::registerDefaultStyles($output);
            static::$output = $output;
        }

        return static::$output;
    }

    /**
     * Register built-in styles if missing (idempotent)
     */
    protected static function registerDefaultStyles(ConsoleOutput $output): void
    {
        $f = $output->getFormatter();

        $styles = [
            // existing (headers use white text on colored background)
            'success'         => ['white', 'green', ['bold']],
            'error'           => ['bright-white', 'red', ['bold']],
            'info'            => ['bright-white', 'cyan', ['bold']],

            // dedicated header tags (avoid conflicts with built-ins)
            'success_header'  => ['bright-white', 'green', ['bold']],
            'info_header'     => ['bright-white', 'cyan', ['bold']],

            // extras
            'yellow'    => ['yellow',       null, ['bold']],
            'green'     => ['green',        null, ['bold']],
            'white'     => ['white',        null, []],
            'muted'     => ['gray',         null, []],
            'warning'   => ['yellow',       null, []],
            'title'     => ['bright-cyan',  null, ['bold']],

            // semantic for help output
            'namespace' => ['cyan',         null, ['bold']],
            'method'    => ['yellow',       null, ['bold']],
            'key'       => ['green',        null, ['bold']],
            'desc'      => ['white',        null, []],
        ];

        foreach ($styles as $name => [$fg, $bg, $opts]) {
            // Force override for listed headers use white text on background.
            // Keep existing built-in styles (e.g., <error>) unless we need to change them.
            $shouldSet = in_array($name, ['info', 'success', 'error'], true) ? true : !$f->hasStyle($name);
            if ($shouldSet) {
                $f->setStyle($name, new OutputFormatterStyle($fg, $bg, $opts));
            }
        }
    }

    /**
     * Add or override a custom style at runtime.
     */
    public static function addStyle(string $name, string $foreground, ?string $background = null, array $options = []): void
    {
        $out = static::getOutput();
        $out->getFormatter()->setStyle($name, new OutputFormatterStyle($foreground, $background, $options));
    }

    /**
     * Generic write with optional STDERR target.
     */
    public static function writeln(string $message, bool $stderr = false): void
    {
        $out = static::getOutput();
        $target = $stderr ? $out->getErrorOutput() : $out;
        $target->writeln($message);
    }

    /**
     * STDERR helper.
     */
    public static function err(string $message): void
    {
        static::writeln($message, true);
    }

    /**
     * Build a styled string from segments.
     * Segment format: ['text' => '...', 'style' => 'method|key|yellow|...']
     */
    public static function segments(array $segments, string $separator = ''): string
    {
        $parts = [];
        foreach ($segments as $seg) {
            $text = (string)($seg['text'] ?? '');
            $style = $seg['style'] ?? null;
            $parts[] = $style ? "<{$style}>{$text}</{$style}>" : $text;
        }
        return implode($separator, $parts);
    }

    /**
     * Print a group/section header (e.g., a namespace or package name).
     * Accepts optional style/color while keeping backward compatibility.
     * - Old: helpHeader('blog-store', true) => STDERR with default 'title' style
     * - New: helpHeader('blog-store', 'green', true)
     */
    public static function helpHeader(string $message, $style = 'title', bool $stderr = false): void
    {
        if (is_bool($style)) {
            $stderr = $style;
            $style = 'title';
        }
        static::writeln("<{$style}>{$message}</{$style}>", $stderr);
    }

    /**
     * Print a help-like item with colored parts and padded description.
     *
     * Example result:
     *   blog-store
     *     blog-store:<yellow>activities</yellow>         Update Blog Views and Clicks...
     *
     * Or with key and method colored separately:
     *     blog-store:<method>register($name)</method> <key>key:force</key>   Description
     */
    public static function helpItem(
        string $namespace,
        string $method,
        ?string $keyGreen,
        string $description,
        int $pad = 35,
        bool $stderr = false,
        array $colorSegments = ['green', 'green']
    ): void {
        $colorSegments = Str::flatten($colorSegments);

        // Build colored label left side
        $label = self::segments([
            ['text' => $namespace . ':', 'style' => $colorSegments[0] ?: 'green'],
            ['text' => $method,    'style' => $colorSegments[1] ?: 'green'],
        ]);

        if (!empty($keyGreen)) {
            $label .= ' ' . self::segments([
                ['text' => $keyGreen, 'style' => 'key'],
            ]);
        }

        // Compute visible length ignoring formatter tags for padding
        $visibleLen = self::visibleLength('  ' . $label); // account for left indent
        $spaces = max(1, $pad - $visibleLen);

        $line = '  ' . $label . str_repeat(' ', $spaces) . self::segments([
            ['text' => $description, 'style' => 'desc'],
        ]);

        static::writeln($line, $stderr);
    }

    /**
     * Visible length of a formatted string (strip tags like <style>...</style>)
     */
    protected static function visibleLength(string $formatted): int
    {
        $stripped = preg_replace('/<\/?[a-zA-Z0-9_-]+>/', '', $formatted) ?? $formatted;
        return strlen($stripped);
    }

    // ---- Simple level helpers (kept for BC) ----
    public static function info(string $message): void
    {
        static::writeln("\n  <info> INFO </info> {$message}");
    }

    public static function success(string $message): void
    {
        static::writeln("\n  <success> SUCCESS </success> {$message}");
    }

    public static function error(string $message): void
    {
        static::writeln("\n  <error> ERROR </error> {$message}");
    }
}