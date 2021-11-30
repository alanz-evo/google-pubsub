<?php

namespace AlanzEvo\GooglePubsub\Abstracts;

use Google\Cloud\PubSub\Message;
use Throwable;

abstract class AbstractThrowableHandler extends AbstractHandler
{
    /**
     * @var Throwable
     */
    protected $throwable;

    /**
     * @param Throwable $throwable
     */
    public function setThrowable(Throwable $throwable)
    {
        $this->throwable = $throwable;

        return $this;
    }
}
