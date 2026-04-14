<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\ChromePdf\Exception;

use RuntimeException;
use Throwable;

final class ConversionFailedException extends RuntimeException
{
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
