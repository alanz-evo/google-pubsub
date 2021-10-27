<?php

namespace AlanzEvo\GooglePubsub\Tests;

use AlanzEvo\GooglePubsub\Exceptions\WrongHandlerException;
use AlanzEvo\GooglePubsub\Exceptions\WrongThrowableHandlerException;
use AlanzEvo\GooglePubsub\HandlerBroker;
use AlanzEvo\GooglePubsub\Tests\Classes\TestThrowableHandler;
use AlanzEvo\GooglePubsub\ThrowableHandlerBroker;
use Exception;
use Google\Cloud\PubSub\Message;
use PHPUnit\Framework\TestCase;

class ThrowableHandlerBrokerTest extends TestCase
{
    /**
     * Test handling the exception successfully 
     */
    public function test_let_handler_handles_exception_successfully()
    {
        $message = new Message(['data' => 'this is a testing data.']);
        $exception = new Exception('test exception');
        $handlerBroker = new ThrowableHandlerBroker();

        $this->assertEquals(
            'test exception',
            $handlerBroker->handle(TestThrowableHandler::class, $message, $exception)
        );
    }

    /**
     * Test handling the message successfully 
     */
    public function test_throw_exception_when_handler_does_not_exist()
    {
        $this->expectException(WrongThrowableHandlerException::class);

        $message = new Message(['data' => 'this is a testing data.']);
        $exception = new Exception('test exception');
        $handlerBroker = new ThrowableHandlerBroker();

        $handlerBroker->handle('SomeNotExistThrowableHandler', $message, $exception);
    }
}