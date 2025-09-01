<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Capsule;

use Whoops\Run;
use Whoops\Handler\PrettyPageHandler;
use Tamedevelopers\Support\Capsule\Manager;

class DebugManager{
    
    public static $whoops;

    /**
     * Boot the DebugManager.
     * If the constant 'TAME_DEBUG_MANAGER' is not defined, 
     * it defines it and starts the debugger automatically.
     * 
     * So that this is only called once in entire application life cycle
     */
    public static function boot()
    {
        if(!defined('TAME_DEBUG_MANAGER')){
            self::autoStartDebugger();
            // Define debug manager as true
            define('TAME_DEBUG_MANAGER', 1);
        } 
    }

    /**
     * Autostart debugger for error logger
     * 
     * @return void
     */
    private static function autoStartDebugger()
    {
        // if DEBUG MODE IS ON
        if(Manager::AppDebug()){
            // header not sent
            if (!headers_sent()) {
                // register error handler
                if (!isset(self::$whoops)) {
                    self::$whoops = new Run();
                    self::$whoops->pushHandler(new PrettyPageHandler());
                    self::$whoops->register();
                }
            }
        } 
    }
    
}