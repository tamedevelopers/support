<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Commands;

use Tamedevelopers\Support\Capsule\Artisan;
use Tamedevelopers\Support\Capsule\Logger;
use Tamedevelopers\Support\Capsule\CommandHelper;


class MakeCommand extends CommandHelper
{   
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make';

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
        Logger::helpHeader('<yellow>Usage:</yellow>');
        Logger::writeln('  php tame make:command [name]');
        Logger::writeln('');
    }

    /**
     * Create a new [Tame] command
     */
    public function command()
    {
        $name  = $this->arguments(0);
        $path = $this->hasOption('path');

        // if not provided, prompt for file name
        if(empty($name)){
            $name = $this->ask("\nWhat should the command be named?");
        }

        Logger::info("Artisan command creation, coming soon: <b>[{$name}]</b>\n");
    }

}