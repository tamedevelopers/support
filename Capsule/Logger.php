<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Capsule;

use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

/**
 * Class Logger
 *
 * A lightweight console logger with styled output for
 * informational, success, and error messages.
 *
 * Usage:
 *   Logger::info("Publishing files...");
 *   Logger::success("File published successfully.");
 *   Logger::error("Failed to publish file.");
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
     *
     * @return ConsoleOutput
     */
    protected static function getOutput(): ConsoleOutput
    {
        if (!static::$output) {
            $output = new ConsoleOutput();

            // Add custom styles
            $output->getFormatter()->setStyle('success', new OutputFormatterStyle('bright-green', null, ['bold']));
            $output->getFormatter()->setStyle('error', new OutputFormatterStyle('bright-red', null, ['bold']));
            $output->getFormatter()->setStyle('info', new OutputFormatterStyle('bright-cyan'));

            static::$output = $output;
        }

        return static::$output;
    }

    /**
     * Log an info message
     *
     * @param string $message
     * @return void
     */
    public static function info(string $message): void
    {
        static::getOutput()->writeln("<info>ℹ $message</info>");
    }

    /**
     * Log a success message
     *
     * @param string $message
     * @return void
     */
    public static function success(string $message): void
    {
        static::getOutput()->writeln("<success>✅ $message</success>");
    }

    /**
     * Log an error message
     *
     * @param string $message
     * @return void
     */
    public static function error(string $message): void
    {
        static::getOutput()->writeln("<error>❌ $message</error>");
    }
}
