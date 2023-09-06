<?php

namespace App\Exception;

use Exception;
class RefreshTokenNotFoundException extends Exception
{
    public function __construct($message, $code = 0, $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}