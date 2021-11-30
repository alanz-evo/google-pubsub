<?php

namespace AlanzEvo\GooglePubsub;

use AlanzEvo\GooglePubsub\Abstracts\AbstractHandler;
use AlanzEvo\GooglePubsub\Exceptions\WrongHandlerException;
use Google\Cloud\PubSub\Message;

class HandlerBroker
{
    /**
     * @var string $handlerClass
     * @var array $subscriptionInfo
     * @var Message $meesage
     */
    public function handle(string $handlerClass, array $subscriptionInfo, Message $message)
    {
        if (class_exists($handlerClass)) {
            $handler = app($handlerClass);
            $isValidHandler = $handler instanceof AbstractHandler;
            if ($isValidHandler) {
                return $handler
                    ->setSubscriptionInfo($subscriptionInfo)
                    ->setMessage($message)
                    ->handle();
            }
        }

        throw new WrongHandlerException();
    }
}
