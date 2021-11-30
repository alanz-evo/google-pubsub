<?php

namespace AlanzEvo\GooglePubsub;

use AlanzEvo\GooglePubsub\Abstracts\AbstractThrowableHandler;
use AlanzEvo\GooglePubsub\Exceptions\WrongThrowableHandlerException;
use Google\Cloud\PubSub\Message;
use Throwable;

class ThrowableHandlerBroker
{
    /**
     * @var string $handlerClass
     * @var array $subscriptionInfo
     * @var Message $meesage
     * @var Throwable $throwable
     */
    public function handle(string $handlerClass, array $subscriptionInfo, Message $message, Throwable $throwable)
    {
        if (class_exists($handlerClass)) {
            $handler = app($handlerClass);
            $isValidHandler = $handler instanceof AbstractThrowableHandler;
            if ($isValidHandler) {
                return $handler->setSubscriptionInfo($subscriptionInfo)
                    ->setMessage($message)
                    ->setThrowable($throwable)
                    ->handle();
            }
        }

        throw new WrongThrowableHandlerException();
    }
}
