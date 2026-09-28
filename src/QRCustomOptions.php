<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use chillerlan\QRCode\QROptions;
use Tamedevelopers\Support\Traits\QRCodeOptionTrait;

class QRCustomOptions extends QROptions
{
    use QRCodeOptionTrait;

    /**
     * @var array
     */
    protected array $paletteColors = [];
    
}