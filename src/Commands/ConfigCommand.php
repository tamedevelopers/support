<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Commands;

use Tamedevelopers\Support\Capsule\CommandHelper;
use Tamedevelopers\Support\Capsule\Logger;
use Tamedevelopers\Support\Installer;


class ConfigCommand extends CommandHelper
{   
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'config {config : The config file to publish}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Config Artisans';


    /**
     * Default entry when running commands.
     *
     * @return void
     */
    public function handle()
    {
        $this->handleHeader('publish');
        Logger::writeln(" config:publish [<name>] --force=[bool]");
        Logger::writeln('');
    }

    public function publish()
    {
        [$method, $force] = [
            $this->argument('name'), 
            (bool) $this->flag('force') ?: false,
        ];

        // since devs can build untop on my publish
        // check if method exists and call the method
        dd(
            $name,
            $force,
        );

        if(method_exists($this, $method)){
            return $this->{$method}($force);
        }

        return $this->error("Method {$method} not found");
    }
    
    /**
     * Publish the mail config file
     */
    public function mail($force = false): string
    {
        $paths = Installer::getPathsData(
            realpath(__DIR__ . '/../')
        );

        Installer::createTameMailer($paths, $force);
    }

}