<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Traits;


trait QRCodeOptionTrait
{   
    /** @var array{shape: string, color: string|null} */
    protected array $shape = [
        'shape' => 'square',
        'color' => null,
    ];

    /** @var array{shape: string, color: string|null} */
    protected array $outerShape = [
        'shape' => 'square',
        'color' => null,
    ];

    /** @var array{shape: string, color: string|null} */
    protected array $innerShape = [
        'shape' => 'square',
        'color' => null,
    ];
}