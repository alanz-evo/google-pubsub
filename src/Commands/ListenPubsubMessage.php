<?php

namespace AlanzEvo\GooglePubsub\Commands;

use Illuminate\Console\Command;
use AlanzEvo\GooglePubsub\Subscriber;
use AlanzEvo\GooglePubsub\MessageAdapter;

class ListenPubsubMessage extends Command
{
    /**
     * The name and signature of the console command.
     * The 'listener' is meaning an index key of the config 'pubsub.listeners' 
     *
     * @var string
     */
    protected $signature = 'listen-pubsub-message '
                            . '{listener : The listener for handling.} '
                            . '{--subscriptionId= : Specify the subscription id to instead of the subscription id of a listener.} '
                            . '{--sleep= : Sleep N ms after a message, default: 1000 ms} '
                            . '{--once : Break the process after handling messages.} '
                            . '{--ackBeforeHandling : Ack to google pub/sub before handling}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pull messages from google pub/sub service.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $listener = $this->argument('listener');
        $subscriptionId = $this->option('subscriptionId');
        $sleep = $this->option('sleep') ?? 1000;
        $once = $this->option('once') ?? false;
        $ackBeforeHandling = $this->option('ackBeforeHandling') ?? false;
        if ($sleep < 0) {
            $sleep = 1000;
        }

        $listenerConfig = config('pubsub.listeners.' . $listener);
        if (is_null($listenerConfig)) {
            $this->error('Invalid listener');
            return 0;
        }

        $subscriber = app(Subscriber::class, [
            'subscriptionId' => $subscriptionId ?? $listenerConfig['subscriptionId'],
            'connection' => $listenerConfig['connection']
        ]);

        $messageAdapter = app(MessageAdapter::class)
            ->setMaxMessages($listenerConfig['maxMessages'] ?? 1)
            ->setMessageLockSec($listenerConfig['messageLockSec'] ?? 30)
            ->setSleepMsPerMessage($sleep)
            ->setHandler($listenerConfig['handler'])
            ->setThrowableHandler($listenerConfig['throwableHandler'] ?? null)
            ->setSubscriber($subscriber)
            ->setAckBeforeHandling($ackBeforeHandling);

        // 持續監聽不中斷，除非 once 為 true
        do {
            $handledCount = $messageAdapter->handle();
            if ($handledCount === 0) {
                usleep(100000);  // 避免持續監聽造成 CPU 使用率一直處於高峰
            }
        } while (! $once);
    }
}
