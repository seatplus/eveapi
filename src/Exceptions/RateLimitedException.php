<?php

namespace Seatplus\Eveapi\Exceptions;

use Exception;

class RateLimitedException extends Exception
{
    public function __construct($message = '', $code = 0, Throwable $previous = null)
    {

        $message = 'Application has produced to many errors. Either the refresh_token has been removed or scope was not granted';

        parent::__construct($message, $code, $previous);
    }
}
