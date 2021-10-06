<?php

namespace AlanzEvo\GooglePubsub\Exceptions;

use Exception;

class WrongDataException extends Exception
{
    public function __construct()
    {
        parent::__construct('The given data is not an array or Arrayable.');
    }
}
