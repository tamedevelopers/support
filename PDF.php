<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use Exception;
use Dompdf\Dompdf;
use Dompdf\Options;
use Tamedevelopers\Support\Env;
use Tamedevelopers\Support\Server;
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
    static private $dompdf;    
     
    /**
     * options
     *
     * @var array
     */
    static private $options = []; 

    /**
     * init
     *
     * @return void
     */
    static private function init() 
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
        self::$dompdf = new Dompdf($options);
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
    static public function create(array $options = [])
    {
        self::$options = array_merge([
            'content'         => '',
            'paper_size'      => 'A4',
            'paper_type'      => 'portrait',
            'destination'     => strtotime('now') . '.pdf',
            'output'          => 'preview',
            'isRemoteEnabled' => false,
            'isFontSubsettingEnabled' => true,
            'isHtml5ParserEnabled' => true,
        ], $options);

        self::init();
        
        // pass html content
        self::$dompdf->loadHtml(self::$options['content']);

        // 'letter', 'legal', 'A4' | array(0,0,609.4488,935.433)
        self::$dompdf->setPaper(self::$options['paper_size'], self::$options['paper_type']);

        // Render the HTML as PDF
        self::$dompdf->render();

        // save pdf to server and render output to browser
        if(in_array(self::$options['output'], ['preview', 'view']))
        {
            @file_put_contents(self::$options['destination'], self::$dompdf->output());

            // render output to browser 
            self::$dompdf->stream(self::$options['destination'], array("Attachment" => false));
        }
        
        // save pdf to server only
        elseif(in_array(self::$options['output'], ['save', 'saves', 'getsave']))
        {
            @file_put_contents(self::$options['destination'], self::$dompdf->output());
        }

        // stream to browser for download
        elseif(in_array(self::$options['output'], ['download', 'downloads']))
        {
            self::$dompdf->stream();
        }
    }

    /**
     * READ PDF To Server
     *
     * @param  string $path --- Absolute path to PDF file
     * @return void
     */
    static public function read(string $path)
    {
        Tame::readPDFToBrowser($path);
    }

    /**
     * Check If DOM PDF has been installed
     *
     * @return mixed
     */
    static private function isDOMPDFInstalled()
    {
        try {
            if (class_exists('Dompdf\Options')) {
                // instantiate and use the \Dompdf\Options() class
                return new Options();
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
