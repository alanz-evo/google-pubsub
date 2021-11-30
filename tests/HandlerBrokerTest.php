<?php

namespace AlanzEvo\GooglePubsub\Tests;

use AlanzEvo\GooglePubsub\Exceptions\WrongHandlerException;
use AlanzEvo\GooglePubsub\HandlerBroker;
use AlanzEvo\GooglePubsub\Tests\Classes\TestHandler;
use Google\Cloud\PubSub\Message;
use PHPUnit\Framework\TestCase;

class HandlerBrokerTest extends TestCase
{
    /**
     * Test handling the message successfully 
     */
    public function test_let_handler_handles_message_successfully()
    {
        $message = new Message(['data' => 'this is a testing data.']);
        $handlerBroker = new HandlerBroker();

        $this->assertEquals(
            'this is a testing data.',
            $handlerBroker->handle(TestHandler::class, [], $message)
        );
    }

    /**
     * Test broker throws an exception when handler does not exist
     */
    public function test_throw_exception_when_handler_does_not_exist()
    {
        $this->expectException(WrongHandlerException::class);

        $message = new Message(['data' => 'this is a testing data.']);
        $handlerBroker = new HandlerBroker();

        $handlerBroker->handle('SomeNotExistHandler', [], $message);
    }
}