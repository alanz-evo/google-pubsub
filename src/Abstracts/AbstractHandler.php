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
     * @var array
     */
    protected $subscriptionInfo = [];

    /**
     * @param Message $message
     */
    public function setMessage(Message $message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @param array $subscriptionInfo
     */
    public function setSubscriptionInfo(array $subscriptionInfo)
    {
        $this->subscriptionInfo = $subscriptionInfo;

        return $this;
    }

    abstract public function handle();
}
