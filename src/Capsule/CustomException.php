<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Capsule;

use Exception;

class CustomException extends Exception {

    /**
     * Constructor to create a custom exception with a custom message.
     *
     * @param string $message The custom error message.
     * @param int $code The error code (default is 0).
     * @param Exception|null $previous The previous exception (default is null).
     */
    public function __construct($message, $code = 0, $previous = null) 
    {
        parent::__construct($message, $code, $previous);
    }
}