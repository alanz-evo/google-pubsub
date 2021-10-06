<?php

namespace AlanzEvo\GooglePubsub\Abstracts;

use Google\Cloud\PubSub\Message;
use Throwable;

abstract class AbstractThrowableHandler
{
    /**
     * @var Message
     */
    protected $message;

    /**
     * @var Throwable
     */
    protected $throwable;

    /**
     * @param Message $message
     */
    public function setMessage(Message $message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @param Throwable $throwable
     */
    public function setThrowable(Throwable $throwable)
    {
        $this->throwable = $throwable;

        return $this;
    }

    abstract public function handle();
}
