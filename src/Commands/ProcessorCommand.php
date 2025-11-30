<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Commands;

use Tamedevelopers\Support\Tame;
use Tamedevelopers\Support\Server;
use Tamedevelopers\Support\ImageToText;
use Tamedevelopers\Support\NameToImage;
use Tamedevelopers\Support\Capsule\File;
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
        $this->handleHeader('processor');
        Logger::writeln("  processor:toImage --name --path --bgColor --textColor --fontWeight=[bold|normal] --type=[circle|radius]  --generate=[bool] \n");
        Logger::writeln('  processor:toText --path= --grayscale=[bool] --contrast=[int]');
        Logger::writeln('');
    }

    /**
     * Convert Name-String to Image File.
     */
    public function toImage(): string|int
    {
        [$name, $bgColor, $textColor, $path, $generate, $output, $fontWeight, $type] = [
            $this->flag('name'), 
            $this->flag('bgColor'), 
            $this->flag('textColor'), 
            $this->flag('path'),
            (bool) $this->flag('generate') ?: false,
            $this->flag('output'),
            $this->flag('fontWeight'), 
            $this->flag('type'), 
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
            'type' => $type, 
        ]));

        $path = Tame::getBasePath($path);

        if ($this->isConsole()) {
            Logger::info("$path\n");
            return 1;
        }

        return $path;
    }

    /**
     * Extract an Image to Text
     */
    public function toText(): string|int
    {
        [$path, $grayscale, $contrast] = [
            $this->flag('path'), 
            (bool) $this->flag('grayscale') ?: true, 
            $this->flag('contrast') ?: 20, 
        ];
        
        $text = ImageToText::run([
            'source' => Tame::getBasePath($path),
            'preprocess' => [
                'grayscale' => $grayscale,
                'contrast'  => $contrast,
            ],
        ]);

        if ($this->isConsole()) {
            Logger::info("$text\n");
            return 1;
        }

        return $text;
    }

}