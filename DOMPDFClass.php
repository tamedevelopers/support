<?php

namespace App\Core;

use Dompdf\Dompdf;
use Dompdf\Options;

/* 
 * DOM PDF
 * @author      DOMPDF
 * @url        composer require dompdf/dompdf
 * @url        https://github.com/dompdf/dompdf/blob/v0.8.2/src/Adapter/CPDF.php#L45
 * @url        https://github.com/dompdf/dompdf
 */ 

class DOMPDFClass
{
    static private $options; 
    static private $dompdf; 
    static private $headers = []; 
    
    //initialization
    static public function init() {
        self::$options = new Options();
        self::$options->set('defaultMediaType', 'all');
        self::$options->set('chroot', self::get_path());
        self::$options->set('isFontSubsettingEnabled', true);
        self::$options->set('isHtml5ParserEnabled', true); 
        self::$options->set('isRemoteEnabled', false);
        self::$options->set('httpContext', [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed'=> true
            ]
        ]);

        // instantiate and use the dompdf class
        self::$dompdf = new Dompdf(self::$options);
    }

    /**
    * Create PDF From HTML STRING
    * 
    */
    static public function create(array $headers = [])
    {
        self::init();
        self::$headers = [
            'html' => $headers['html'] ?? '',
            'paper_size' => $headers['paper_size'] ?? 'A4',
            'paper_type' => $headers['paper_type'] ?? 'portrait',
            'file_path' => $headers['file_path'] ?? strtotime('now') . '.pdf',
            'output' => $headers['output'] ?? 'preview',
        ];
        
        // pass html content
        self::$dompdf->loadHtml(self::$headers['html']);

        // 'letter', 'legal', 'A4' | array(0,0,609.4488,935.433)
        self::$dompdf->setPaper(self::$headers['paper_size'], self::$headers['paper_type']);

        // Render the HTML as PDF
        self::$dompdf->render();

        if(in_array(self::$headers['output'], ['preview', 'view'])){
            // save pdf to server
            @file_put_contents(self::$headers['file_path'], self::$dompdf->output());

            // render output to browser 
            self::$dompdf->stream(self::$headers['file_path'], array("Attachment" => false));
        }
        
        elseif(in_array(self::$headers['output'], ['save', 'saves', 'getsave'])){
            // save pdf to server
            @file_put_contents(self::$headers['file_path'], self::$dompdf->output());
        }

        elseif(in_array(self::$headers['output'], ['download', 'downloads'])){
            // stream to browser for download
            self::$dompdf->stream();
        }
    }

    /**
    * READ PDF From Server
    * 
    */
    static protected function readServerPDF(string $path_to_pdf)
    {
        // Header content type
        header("Content-type: application/pdf");
        
        header("Content-Length: " . filesize($path_to_pdf));
        
        // Send the file to the browser.
        readfile($path_to_pdf);
    }

    /**
     * get project base path
    */
    static public function get_path(?string $path = null)
    {
        if(empty($path)){
            if(function_exists('public_path')){
                $directory = str_replace('\\', '/',  public_path('\\'));
            }else{
                $directory = str_replace('\\', '/', realpath('.')) . '/';
            }
        }else{
            $directory = str_replace('\\', '/', $path) . '/';
        }
        return $directory;
    }

}
