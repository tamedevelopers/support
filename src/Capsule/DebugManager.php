<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Capsule;

use Whoops\Run;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Tamedevelopers\Support\Capsule\File;
use Tamedevelopers\Support\Capsule\Manager;

class DebugManager
{
    /**
     * Track whether the boot process has completed.
     */
    protected static bool $booted = false;

    /**
     * The Whoops Run instance.
     */
    protected static ?Run $whoops = null;

    /**
     * Boot the DebugManager.
     * 
     * Ensures the debugger registers only once during the application lifecycle.
     */
    public static function boot(): void
    {
        if (self::$booted) {
            return;
        }

        self::$booted = true;
        self::autoStartDebugger();
    }

    /**
     * Check if DebugManager has been booted.
     */
    public static function isBooted(): bool
    {
        return self::$booted;
    }

    /**
     * Autostart debugger for error logging and exception handling.
     */
    private static function autoStartDebugger(): void
    {
        // Check if debug mode is active
        if (Manager::AppDebug()) {
            return;
        }

        if (self::$whoops === null) {
            self::$whoops = new Run();

            // Register appropriate handler based on SAPI environment
            if (self::isCli()) {
                self::$whoops->pushHandler(new PlainTextHandler());
            } else {
                $handler = new PrettyPageHandler();

                // Customize Page Title & Editor link formatting if needed
                $handler->setPageTitle("Application Exception");
                
                // Ensure HTTP 500 status code is set when rendering error pages
                $handler->addDataTableCallback('HTTP Status', function () {
                    if (!headers_sent()) {
                        http_response_code(500);
                    }
                    return [];
                });

                // Path to your Dummy directory
                $dummyDir       = __DIR__ . '/Dummy';
                $themeFileName  = 'whoops_ignition_theme.css';
                $themeFilePath  = "{$dummyDir}/{$themeFileName}";

                if (File::exists($themeFilePath)) {
                    // Tell Whoops to look in your custom directory for resources
                    $handler->addResourcePath($dummyDir);

                    // Pass only the CSS filename (relative to the registered resource path)
                    $handler->addCustomCss($themeFileName);
                }

                self::$whoops->pushHandler($handler);
            }

            self::$whoops->register();
        }
    }

    /**
     * Check if the application is running in CLI mode.
     */
    protected static function isCli(): bool
    {
        return PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
    }

    /**
     * Get the registered Whoops instance.
     */
    public static function getWhoops(): ?Run
    {
        return self::$whoops;
    }

    /**
     * Unregister Whoops and reset the booted state.
     * Useful for isolated unit testing environment teardowns.
     */
    public static function flush(): void
    {
        if (self::$whoops !== null) {
            self::$whoops->unregister();
            self::$whoops = null;
        }

        self::$booted = false;
    }

}