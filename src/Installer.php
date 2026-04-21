<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;


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
     * Dump default files into the user project root
     */
    protected static function publishDefaults()
    {
        // dummy paths to be created 
        $paths = self::getPathsData(realpath(__DIR__));

        // only create when files are not present
        if(self::isDummyNotPresent($paths)){
            // create for [tame] 
            self::createTameBash($paths);

            // create for [database.php]
            // self::createTameMailer($paths);
        }
    }

    /**
     * Create [tame] file if not exist
     */
    private static function createTameBash($paths) : void
    {
        $tame = $paths['tame'];

        if(!File::exists($tame['path'])){
            // Read the contents of the dummy file
            $dummyContent = File::get($tame['dummy']);

            // Write the contents to the new file
            File::put($tame['path'], $dummyContent);

            Logger::info("\n<b>[Tame-Artisan]</b> has been created automatically!\n\nUsage: \n   php tame <command> [:option] [arguments]\n\n");
        }
    }

    /**
     * Create [database.php] file if not exist
     */
    public static function createTameMailer($paths, $force = false) : void
    {
        $mail = $paths['mail'];

        if(!File::exists($mail['path']) || $force === true){

            self::createConfigDirectory($paths);

            // Read the contents of the dummy file
            $dummyContent = File::get($mail['dummy']);

            // Write the contents to the new file
            File::put($mail['path'], $dummyContent);

            Logger::info("\n<b>[Mail Config]</b> has been publised successfully!\n\n");
        }
    }

    /**
     * Get dummy contents path data
     * 
     * @return array
     */
    public static function getPathsData($realPath = null)
    {
        $env        = new Env();
        $server     = Env::getServers('server');
        $serverPath = $env->cleanServerPath( $server );
        $realPath   = rtrim($env->cleanServerPath( $realPath ), '/');

        return [
            'tame' => [
                'path'  => "{$serverPath}tame",
                'dummy' => "{$realPath}/Capsule/Dummy/dummyTame.dum",
            ],
            'mail' => [
                'path'  => "{$serverPath}config/mail.php",
                'dummy' => "{$realPath}/Capsule/Dummy/dummyMail.dum",
            ],
            'disposable' => [
                'path'  => $realPath,
                'dummy' => "/Capsule/Dummy/disposableEmails.dum",
            ],
        ];
    }

    /**
     * Check if dummy data is present
     * 
     * @return bool
     */
    protected static function isDummyNotPresent($paths)
    {
        $present = [false];
        
        // create for tame
        if(!File::exists($paths['tame']['path'])){
            $present[] = true;
        }
        
        // create for mail
        if(!File::exists($paths['mail']['path'])){
            $present[] = true;
        }

        // Check if all elements in $present are false
        $allFalse = empty(array_filter($present));
        
        // All elements in $present are false
        if ($allFalse) {
            return false;
        }

        return true;
    }

    /**
     * Create Configuration directory is not exists
     */
    protected static function createConfigDirectory($paths = null): void
    {
        // folder path
        $configFolder = str_replace(
            ['mail.php'], '', $paths['mail']['path']
        );

        // if config folder not found
        if(!File::isDirectory($configFolder)){
            File::makeDirectory($configFolder, 0777);
        }
    }

}
