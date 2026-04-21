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

    /**
     * Publish a configuration file [<name>] --force=[bool]
     */
    public function publish()
    {
        [$method, $force] = [
            $this->argument('name'),
            (bool) $this->flag('force') ?: false,
        ];

        // Allow extending publish targets by adding methods (e.g. `mail`).
        // Only dispatch to public methods on this command class.
        if (empty($method)) {
            return $this->error('Publish target is required. Example: config:publish mail');
        }

        if (method_exists($this, $method) && is_callable([$this, $method])) {
            return $this->{$method}($force);
        }

        return $this->error("Publish target [{$method}] not found");
    }
    
    /**
     * Publish the mail config file
     */
    private function mail($force = false): void
    {
        $paths = Installer::getPathsData(
            realpath(__DIR__ . '/../')
        );

        $choice = $this->choice(
            'Select the mail config file to publish',
            ['mail', 'tame'],
            'mail'
        );

        dd(
            $choice,
        );

        Installer::createTameMailer($paths, $force);
    }

}