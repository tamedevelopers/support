<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Traits;

use Symfony\Component\VarDumper\VarDumper;

trait ReusableTrait{

    /**
     * Die or Dump Error Handler
     * @param mixed $data
     *  
     * @return mixed
     */
    public function dump(...$data)
    {
        $dataArray = $data[0] ?? $data;
        if(is_array($dataArray)){
            foreach ($dataArray as $var) {
                VarDumper::dump($var);
            }
        } else{
            VarDumper::dump($dataArray);
        }
    }

}