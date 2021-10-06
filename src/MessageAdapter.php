<?php

namespace AlanzEvo\GooglePubsub;

use Google\Cloud\PubSub\Message;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use AlanzEvo\GooglePubsub\Subscriber;
use AlanzEvo\GooglePubsub\HandlerBroker;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Throwable;

class MessageAdapter
{
    const SITUATION_SUCCESS = 'success';
    const SITUATION_FAIL = 'fail';
    const SITUATION_LOCKING = 'locking';

    /**
     * @var Subscriber
     */
    protected $subscriber;

    /**
     * @var int
     */
    protected $maxMessages = 100;

    /**
     * @var int
     */
    protected $messageLockSec = 30;

    /**
     * @var string
     */
    protected $handler;

    /**
     * @var string
     */
    protected $throwableHandler;

    /**
     * @var int
     */
    protected $sleepMsPerMessage = 1000;

    /**
     * @param Subscriber $subscriber
     */
    public function setSubscriber(Subscriber $subscriber)
    {
        $this->subscriber = $subscriber;

        return $this;
    }

    /**
     * @param int $maxMessages
     */
    public function setMaxMessages(int $maxMessages)
    {
        $this->maxMessages = $maxMessages;

        return $this;
    }

    /**
     * @param int $messageLockSec
     */
    public function setMessageLockSec(int $messageLockSec)
    {
        $this->messageLockSec = $messageLockSec;

        return $this;
    }

    /**
     * @param string $handler
     */
    public function setHandler(string $handler)
    {
        $this->handler = $handler;

        return $this;
    }

    /**
     * @param string|null $throwableHandler
     */
    public function setThrowableHandler(string $throwableHandler = null)
    {
        $this->throwableHandler = $throwableHandler;

        return $this;
    }

    /**
     * @param int $sleepMsPerMessage
     */
    public function setSleepMsPerMessage(int $sleepMsPerMessage)
    {
        $this->sleepMsPerMessage = $sleepMsPerMessage;

        return $this;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $handledCount = 0;
        $messages = $this->subscriber->pull(['maxMessages' => $this->maxMessages]);
        foreach ($messages as $message) {
            $situation = $this->putMessageToHandlerAndHandle($this->handler, $this->messageLockSec, $message, $this->throwableHandler);
            if ($situation === static::SITUATION_SUCCESS) {
                $this->subscriber->acknowledge($message);  // 確認 message 已收到並處理，回傳確認給 google pub/sub
                                                           // 不確認的話，同樣的 Message 會再一次被拿到
            } elseif ($situation === static::SITUATION_FAIL) {
                $this->subscriber->modifyAckDeadline($message, 0);  // 失敗時就丟回 google pub/sub
            } else {
                continue;  // 因 Message 正被別的 Handler 執行中時就不等待
            }
            $handledCount++;
            usleep($this->sleepMsPerMessage * 1000);
        }

        return $handledCount;
    }

    protected function putMessageToHandlerAndHandle(string $handler, int $lockSec, Message $message, string $throwableHandler = null): string
    {
        $situation = static::SITUATION_LOCKING;
        $lockKey = 'pubsub-lock:' . $message->ackId();
        try {
            $lock = Cache::lock($lockKey, $lockSec);  // 避免多個 Handler 造成同樣的 message 同時被執行
            $lock->block(0, function () use ($message, $handler, $throwableHandler, &$situation) {
                try {
                    app(HandlerBroker::class)->handle($handler, $message);
                    $situation = static::SITUATION_SUCCESS;
                } catch (Throwable $th) {
                    $situation = static::SITUATION_FAIL;
                    if (empty($throwableHandler)) {
                        Log::error($th);
                    } else {
                        app(ThrowableHandlerBroker::class)->handle($throwableHandler, $message, $th);
                    }
                }
            });
        } catch (LockTimeoutException $e) {
            Log::error($e);
            // Skip
        }

        return $situation;
    }
}
