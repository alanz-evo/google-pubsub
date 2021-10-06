<?php

namespace AlanzEvo\GooglePubsub\Exceptions;

use Exception;

class WrongThrowableHandlerException extends Exception
{
    public function __construct()
    {
        parent::__construct('The given exception handler is not a valid handler.');
    }
}
