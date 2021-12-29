<?php

namespace AlanzEvo\GooglePubsub;

use Google\Cloud\PubSub\Message;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use AlanzEvo\GooglePubsub\Subscriber;
use AlanzEvo\GooglePubsub\HandlerBroker;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Throwable;

class MessageListener
{
    const SITUATION_SUCCESS = 'success';
    const SITUATION_FAIL = 'fail';

    /**
     * @var bool
     */
    protected $terminated = false;

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
     * @var bool 
     */
    protected $ackBeforeHandling = false;

    /**
     * @var int
     */
    protected $sleepMsPerMessage = 1000;

    /**
     * @var bool
     */
    protected $once = false;

    /**
     * @var bool
     */
    protected $isChild = false;
    
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
     * @param bool $ackBeforeHandling
     */
    public function setAckBeforeHandling(bool $ackBeforeHandling)
    {
        $this->ackBeforeHandling = $ackBeforeHandling;

        return $this;
    }

    /**
     * @param bool $once
     */
    public function setOnce(bool $once)
    {
        $this->once = $once;

        return $this;
    }

    public function setIsChild(bool $isChild)
    {
        $this->isChild = $isChild;

        return $this;
    }

    /**
     * Pull and handle messages loop.
     */
    public function loop()
    {
        $this->listenForSignals();

        // 持續監聽不中斷，除非 once 為 true
        do {
            try {
                if ($this->pullAndHandleMessages() == 0) {
                    usleep(100000);  // 避免持續監聽造成 CPU 使用率一直處於高峰
                }
            } catch (Throwable $th) {
                Log::error($th);
                sleep(1);
            }
        } while ($this->shouldContinue());

        posix_kill(getmypid(), SIGKILL);
    }

    protected function shouldContinue()
    {
        $ppid = posix_getppid();
        
        return !$this->once
            && !$this->terminated
            && (!$this->isChild || $ppid !== 1);
    }

    protected function listenForSignals()
    {
        pcntl_async_signals(true);
        
        pcntl_signal(SIGTERM, function() {
            $this->terminateSelf();
        });

        pcntl_signal(SIGINT, function() {
            $this->terminateSelf();
        });
    }

    protected function terminateSelf()
    {
        $this->terminated = true;
    }

    protected function pullAndHandleMessages()
    {
        $handledCount = 0;
        $messages = $this->subscriber->pull(['maxMessages' => $this->maxMessages]);
        foreach ($messages as $message) {
            try {
                $lockKey = 'pubsub-lock:' . $message->ackId();
                $lock = Cache::lock($lockKey, $this->messageLockSec);  // 避免多個 Handler 造成同樣的 message 同時被執行
                $lock->block(0, function () use ($message, &$handledCount) {
                    if ($this->ackBeforeHandling) {
                        $this->subscriber->acknowledge($message);  // 確認 message 已收到並處理，回傳確認給 google pub/sub
                    }
                    $situation = $this->putMessageToHandlerAndHandle($message, $this->handler, $this->throwableHandler);
                    if ($situation === static::SITUATION_SUCCESS && !$this->ackBeforeHandling) {
                        $this->subscriber->acknowledge($message);  // 確認 message 已收到並處理，回傳確認給 google pub/sub
                    }

                    $handledCount++;
                    usleep($this->sleepMsPerMessage * 1000);
                });
            } catch (LockTimeoutException $e) {
                // Skip
            } catch (Throwable $th) {
                Log::error($th);
            }
        }

        return $handledCount;
    }

    protected function putMessageToHandlerAndHandle(Message $message, string $handler, string $throwableHandler = null): string
    {
        try {
            $subscriptionInfo = $this->subscriber->getInfo();
            app(HandlerBroker::class)->handle($handler, $subscriptionInfo, $message);
            $situation = static::SITUATION_SUCCESS;
        } catch (Throwable $th) {
            $situation = static::SITUATION_FAIL;
            if (empty($throwableHandler)) {
                Log::error($th);
            } else {
                app(ThrowableHandlerBroker::class)->handle($throwableHandler, $subscriptionInfo, $message, $th);
            }
        }

        return $situation;
    }
}
