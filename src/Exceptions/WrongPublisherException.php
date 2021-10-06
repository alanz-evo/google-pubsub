<?php

namespace AlanzEvo\GooglePubsub\Exceptions;

use Exception;

class WrongPublisherException extends Exception
{
    public function __construct(string $name)
    {
        parent::__construct('The given publisher is not a valid publisher. Given name: ' . $name);
    }
}
