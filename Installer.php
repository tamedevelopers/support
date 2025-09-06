<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;


use Tamedevelopers\Support\Tame;
use Tamedevelopers\Support\Capsule\File;
use Tamedevelopers\Support\Capsule\Logger;

class Installer
{
    /**
     * Run after composer require/install
     */
    public static function install()
    {
        self::publishDefaults();
    }

    /**
     * Run after composer update
     */
    public static function update()
    {
        self::publishDefaults();
    }

    /**
     * Backward-compat: called by ComposerPlugin hooks
     */
    public static function postInstall(): void
    {
        self::install();
    }

    /**
     * Backward-compat: called by ComposerPlugin hooks
     */
    public static function postUpdate(): void
    {
        self::update();
    }

    /**
     * Dump default files into the user project root
     */
    protected static function publishDefaults()
    {
        // if app is running inside of a framework
        $frameworkChecker = (new Tame)->isAppFramework();

        // if application is not a framework, 
        // then we can start dupping default needed files
        if(! $frameworkChecker){
            // dummy paths to be created 
            $paths = self::getPathsData(realpath(__DIR__));

            // only create when files are not present
            if(self::isDummyNotPresent($paths)){
                // create for [tame] 
                self::createTameBash($paths);

                Logger::info("\n<b>[Tame-Artisan]</b> has been created automatically!\n\nUsage: \n   php tame <command> [:option] [arguments]\n\n");
            }
        }
    }

    /**
     * Create [tame] file if not exist
     */
    private static function createTameBash($paths) : void
    {
        if(!File::exists($paths['path'])){
            // Read the contents of the dummy file
            $dummyContent = File::get($paths['dummy']);

            // Write the contents to the new file
            File::put($paths['path'], $dummyContent);
        }
    }

    /**
     * Check if dummy data is present
     * 
     * @return bool
     */
    private static function isDummyNotPresent($paths)
    {
        return ! File::exists($paths['path']);
    }

    /**
     * Get dummy contents path data
     * 
     * @return array
     */
    protected static function getPathsData($realPath = null)
    {
        $env        = new Env();
        $server     = Env::getServers('server');
        $serverPath = $env->cleanServerPath( $server );
        $realPath   = rtrim($env->cleanServerPath( $realPath ), '/');

        return [
            'path'  => "{$serverPath}tame",
            'dummy' => "{$realPath}/Capsule/Dummy/dummyTame.dum",
        ];
    }

}
