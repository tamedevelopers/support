<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Commands;


use Tamedevelopers\Support\Capsule\File;
use Tamedevelopers\Support\Capsule\Logger;
use Tamedevelopers\Support\Capsule\CommandHelper;
use Tamedevelopers\Support\Commands\Traits\ServiceTrait;


class MakeCommand extends CommandHelper
{   

    use ServiceTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make {name : The full name (optionally nested) of the service class}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make Artisans';

    /**
     * Default entry when running commands.
     *
     * @return void
     */
    public function handle()
    {
        $this->handleHeader('make');
        Logger::writeln('  make:command [name]');
        Logger::writeln('  make:service [name]');
        Logger::writeln('');
    }

    /**
     * Create a new <command>
     */
    public function command()
    {
        $name = $this->argument('name');

        // if not provided, prompt for file name
        if(empty($name)){
            $name = $this->ask("\nWhat should the command be named?");
        }

        Logger::info("Tame command creation, coming soon: <b>[{$name}]</b>\n");
    }

    /**
     * Create a new service class with proper namespace and folders
     */
    public function service()
    {
        [$className, $namespace, $filePath, $directory] = $this->parseInput();

        if ($this->serviceExists($filePath)) {
            return;
        }

        $this->ensureDirectoryExists($directory);
        
        File::put($filePath, $this->buildClassStub($className, $namespace));

        Logger::info("Service [{$filePath}] created successfully.");
    }
    
}