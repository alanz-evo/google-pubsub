<?php

namespace AlanzEvo\GooglePubsub\Abstracts;

use Google\Cloud\PubSub\Message;

abstract class AbstractHandler
{
    /**
     * @var Message
     */
    protected $message;

    /**
     * @param Message $message
     */
    public function setMessage(Message $message)
    {
        $this->message = $message;

        return $this;
    }

    abstract public function handle();
}
