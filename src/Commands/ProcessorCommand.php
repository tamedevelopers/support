<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Commands;

use Tamedevelopers\Support\Server;
use Tamedevelopers\Support\ImageToText;
use Tamedevelopers\Support\NameToImage;
use Tamedevelopers\Support\Capsule\Logger;
use Tamedevelopers\Support\Capsule\CommandHelper;


class ProcessorCommand extends CommandHelper
{   
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'processor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Processor Artisans';


    /**
     * Default entry when running commands.
     *
     * @return void
     */
    public function handle()
    {
        Logger::helpHeader('<yellow>Usage:</yellow>');
        Logger::writeln('  php tame make:processor [name]');
        Logger::writeln('  php tame make:processor [name]');
        Logger::writeln('');
    }

    /**
     * Convert Name-String to Image File.
     */
    public function toImage(): string
    {
        $args  = $this->arguments();
        [$name, $bgColor, $textColor, $path, $generate, $output, $fontWeight] = [
            $this->flag('name'), 
            $this->flag('bgColor'), 
            $this->flag('textColor'), 
            $this->flag('path'),
            $this->flag('output'),
            (bool) $this->flag('generate') ?: false,
            $this->flag('fontWeight'), 
        ];

        if(!in_array($output, ['save', 'data'])){
            $output = 'save';
        }

        $path = Server::pathReplacer(NameToImage::run([
            'name' => $name,
            'bg_color' => $bgColor,
            'font_weight' => $fontWeight,
            'text_color' => $textColor,
            'destination' => $path,
            'output' => $output, 
            'generate' => $generate, 
        ]));

        Logger::info("$path\n");

        return $path;
    }

    /**
     * Convert and Extract Image to Text
     */
    public function toText(): string
    {
        $args  = $this->arguments();
        [$name, $bgColor, $textColor, $path, $generate, $output, $fontWeight] = [
            $this->flag('name'), 
            $this->flag('bgColor'), 
            $this->flag('textColor'), 
            $this->flag('path'),
            $this->flag('output'),
            (bool) $this->flag('generate') ?: false,
            $this->flag('fontWeight'), 
        ];

        if(!in_array($output, ['save', 'data'])){
            $output = 'save';
        }

        $path = Server::pathReplacer(ImageToText::run([
            'name' => $name,
            'bg_color' => $bgColor,
            'font_weight' => $fontWeight,
            'text_color' => $textColor,
            'destination' => $path,
            'output' => $output, 
            'generate' => $generate, 
        ]));

        Logger::info("$path\n");

        return $path;
    }

}