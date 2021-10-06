<?php

namespace AlanzEvo\GooglePubsub\Exceptions;

use Exception;

class WrongSubscriberException extends Exception
{
    public function __construct(string $name)
    {
        parent::__construct('The given subscriber is not a valid subscriber. Given name: ' . $name);
    }
}
