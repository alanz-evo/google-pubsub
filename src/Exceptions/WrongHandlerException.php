<?php

namespace AlanzEvo\GooglePubsub\Exceptions;

use Exception;

class WrongHandlerException extends Exception
{
    public function __construct()
    {
        parent::__construct('The given handler is not a valid handler.');
    }
}
