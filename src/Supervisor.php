<?php

namespace AlanzEvo\GooglePubsub;

class Supervisor
{
    /**
     * @var bool
     */
    protected $terminated = false;

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
     * @var array
     */
    protected $monitoringListeners = [];

    /**
     * @var bool
     */
    protected $alreadyListenSignals = false;

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

    /**
     * Pull and handle messages loop.
     */
    public function monitor()
    {
        $listeners = config('pubsub.listeners');

        foreach ($listeners as $name => $config) {
            $processNum = $config['process_num'] ?? 1;
            for ($i = 1; $i <= $processNum; $i++) {
                $this->monitoringListeners[$name . ':' . $i] = [
                    'config' => $config,
                    'pid' => null,
                    'start_time' => null,
                ];
            }
        }

        while (!$this->terminated) {
            $this->startUp();
            sleep(5);
        }
    }

    protected function startUp()
    {
        foreach ($this->monitoringListeners as $name => &$listener) {
            if ($this->childIsExited($listener['pid'])) {
                $pid = pcntl_fork();
                if ($pid == -1) {
                    exit;
                } elseif ($pid) {
                    $listener['pid'] = $pid;
                    $listener['start_time'] = time();
                } else {
                    echo "Start up the listener `$name`.\n";
                    $this->startUpListener($listener['config']); 
                    echo "The listener `$name` stopped.\n";  
                    exit; 
                }
            }
        }

        if (!$this->alreadyListenSignals) {
            $this->listenForSignals();
            $this->alreadyListenSignals = true;
        }
    }

    /**
     * @param int|null $pid
     */
    protected function childIsExited(int $pid = null)
    {
        if (!is_null($pid)) {
            $res = pcntl_waitpid($pid, $status, WNOHANG);
            if ($res == 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array $config
     */
    protected function startUpListener(array $config)
    {
        $subscriber = app(Subscriber::class, [
            'subscriptionId' => $config['subscriptionId'],
            'connection' => $config['connection']
        ]);

        app(MessageListener::class)
            ->setMaxMessages($config['maxMessages'] ?? 1)
            ->setMessageLockSec($config['messageLockSec'] ?? 30)
            ->setSleepMsPerMessage($this->sleepMsPerMessage)
            ->setHandler($config['handler'])
            ->setThrowableHandler($config['throwableHandler'] ?? null)
            ->setSubscriber($subscriber)
            ->setAckBeforeHandling($this->ackBeforeHandling)
            ->setOnce($this->once)
            ->loop();
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

        foreach ($this->monitoringListeners as $listener) {
            if (!$this->childIsExited($listener['pid'])) {
                posix_kill($listener['pid'], SIGTERM);
            }
        }
    }
}
