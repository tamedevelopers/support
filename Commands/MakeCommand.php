<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Commands;


use Tamedevelopers\Support\Capsule\Logger;
use Tamedevelopers\Support\Capsule\CommandHelper;


class MakeCommand extends CommandHelper
{   
    /**
     * Default entry when running command
     */
    public function handle(array $args = [], array $options = []): int
    {
        echo "Usage examples:\n";
        echo "  php tame make\n";
        echo "  php tame make:command [name] --path=users\n\n";
        return 0;
    }

    /**
     * Create a new [Tame-Artisan] command
     */
    public function command(array $args = [], array $options = []): int
    {
        $name  = $args[0] ?? null;
        $path = $this->getOption($options, 'path');

        // if not provided, prompt for file name
        if(empty($name)){
            $name = $this->ask("\nWhat should the command be named?");
        }

        Logger::info("Default Artisan command <b>[{$name}]</b>: coming soon!\n");
        exit(1);
    }

}