<?php

namespace AlanzEvo\GooglePubsub\Exceptions;

use Exception;

class WrongConnectionException extends Exception
{
    public function __construct(string $name)
    {
        parent::__construct('The given connection is not a valid connection. Given name: ' . $name);
    }
}
