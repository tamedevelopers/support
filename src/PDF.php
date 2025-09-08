<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use Exception;
use Dompdf\Dompdf;
use Dompdf\Options;
use Tamedevelopers\Support\Env;
use Tamedevelopers\Support\Server;
use Tamedevelopers\Support\Capsule\File;
use Tamedevelopers\Support\Capsule\Manager;
use Tamedevelopers\Support\Capsule\CustomException;

/**
 * DOM PDF Wrapper
 * Usage: composer require dompdf/dompdf
 * @link https://github.com/dompdf/dompdf/blob/v0.8.2/src/Adapter/CPDF.php#L45
 * @link https://github.com/dompdf/dompdf
 */ 
class PDF{

    /**
     * dompdf
     *
     * @var mixed
     */
    private static $dompdf;    
     
    /**
     * options
     *
     * @var array
     */
    private static $options = []; 

    /**
     * init
     *
     * @return void
     */
    private static function init() 
    {
        $options = self::isDOMPDFInstalled();
        $options->set('defaultMediaType', 'all');
        $options->set('chroot', Server::cleanServerPath( public_path('\\') ) );
        $options->set('isFontSubsettingEnabled', self::$options['isFontSubsettingEnabled']);
        $options->set('isHtml5ParserEnabled', self::$options['isHtml5ParserEnabled']); 
        $options->set('isRemoteEnabled', self::$options['isRemoteEnabled']);
        $options->set('httpContext', [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed'=> true
            ]
        ]);

        // instantiate and use the \Dompdf\Dompdf() class
        $dompdf = '\Dompdf\Dompdf';
        self::$dompdf = new $dompdf($options);
    }

    /**
     * Create PDF From HTML STRING
     *
     * @param  array $options
     * - Keys [content|paper_size|paper_type|output|destination]
     * 
     * - `paper_type` key value --- [portrait|landscape]
     * - `paper_size` key value --- [letter|legal|A4]
     * - `output` key value --- [view|save|download]
     * 
     * @return void
     */
    public static function create(array $options = [])
    {
        self::$options = array_merge([
            'content'         => '',
            'paper_size'      => 'A4',
            'paper_type'      => 'portrait',
            'destination'     => strtotime('now') . '.pdf',
            'output'          => 'preview',
            'title'           => null,
            'delete'          => true,
            'isRemoteEnabled' => false,
            'isFontSubsettingEnabled' => true,
            'isHtml5ParserEnabled' => true,
        ], $options);

        self::init();

        // Get the destination path
        $destination = Tame()->stringReplacer(self::$options['destination']);

        // Make directory
        File::makeDirectory(
            dirname($destination)
        );

        // Get the HTML content
        $content = self::$options['content'];

        // if title is empty, then use the file name as title
        if(empty(self::$options['title'])){
            self::$options['title'] = pathinfo($destination, PATHINFO_FILENAME);
        }

        // Add the title tag if <html> or <head> is not present
        if (!empty(self::$options['title'])) {
            // Check if the content contains <html> or <head> tags
            if (strpos($content, '<html>') === false && strpos($content, '<head>') === false) {
                $content = '<html><head><title>' . htmlspecialchars(self::$options['title']) . '</title></head><body>' . $content . '</body></html>';
            }
        }
        
        // pass html content
        self::$dompdf->loadHtml($content);

        // 'letter', 'legal', 'A4' | array(0,0,609.4488,935.433)
        self::$dompdf->setPaper(self::$options['paper_size'], self::$options['paper_type']);

        // Render the HTML as PDF
        self::$dompdf->render();

        // Render PDF output to browser without saving
        if(in_array(self::$options['output'], ['preview', 'view']))
        {
            File::put($destination, self::$dompdf->output());

            // for reading to browser as well, from package
            // self::$dompdf->stream($destination, array("Attachment" => false)); 

            // render output to browser 
            Tame::readPDFToBrowser($destination, self::$options['delete']);
        }
        
        // Save PDF to server only
        elseif(in_array(self::$options['output'], ['save', 'saves', 'getsave']))
        {
            File::put($destination, self::$dompdf->output());
        }

        // Stream PDF to browser for download
        elseif(in_array(self::$options['output'], ['download', 'downloads']))
        {
            self::$dompdf->stream();
        }
    }

    /**
     * READ PDF To Server
     *
     * @param  string $path
     * [Absolute path to PDF file]
     * 
     * @return void
     */
    public static function read(string $path)
    {
        Tame::readPDFToBrowser($path);
    }

    /**
     * Check If DOM PDF has been installed
     *
     * @return mixed
     */
    private static function isDOMPDFInstalled()
    {
        try {
            if (class_exists('Dompdf\Options')) {
                // instantiate and use the \Dompdf\Options() class
                $option = '\Dompdf\Options';
                return new $option();
            } else {
                throw new CustomException(
                    "Class Dompdf\Options not found: \nRequire the package by running: `composer require dompdf/dompdf`\n" . 
                    (new Exception)->getTraceAsString()
                );
            }
        } catch (CustomException $e) {
            // Handle the exception silently (turn off error reporting)
            error_reporting(0);

            Manager::setHeaders(404, function() use($e){

                // create error logger
                Env::bootLogger();

                // Trigger a custom error
                trigger_error($e->getMessage(), E_USER_ERROR);
            });
        }
    }

}
