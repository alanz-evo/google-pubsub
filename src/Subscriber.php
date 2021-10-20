<?php

namespace AlanzEvo\GooglePubsub;

use AlanzEvo\GooglePubsub\Exceptions\WrongConnectionException;
use AlanzEvo\GooglePubsub\Exceptions\WrongSubscriberException;
use Google\Cloud\PubSub\Message;
use Google\Cloud\PubSub\PubSubClient;
use Google\Cloud\PubSub\Subscription;

class Subscriber
{
    /**
     * @var Subscription|null
     */
    protected $subscription = null;

    /**
     * @var string
     */
    protected $subscriberName = '';

    public function __construct(string $subscriberName = 'default')
    {
        $this->subscriberName = $subscriberName;
    }

    /**
     * @param array $options
     * @return array
     */
    public function pull(array $options = []): array
    {
        $subscription = $this->getSubscription();

        return $subscription->pull($options);
    }

    /**
     * @param Message $message
     * @param array $options
     */
    public function acknowledge(Message $message, array $options = [])
    {
        $subscription = $this->getSubscription();

        $subscription->acknowledge($message, $options);
    }

    /**
     * @param Message $message
     * @param int $seconds
     * @param array $options
     */
    public function modifyAckDeadline(Message $message, int $seconds, array $options = [])
    {
        $subscription = $this->getSubscription();

        $subscription->modifyAckDeadline($message,$seconds, $options);
    }

    protected function getSubscription(): Subscription
    {
        if (is_null($this->subscription)) {
            $config = $this->getConfig();
            $pubSub = app(PubSubClient::class, [
                'config' => $config['connectionConfig'],
            ]);

            $this->subscription = $pubSub->subscription($config['subscriber']);
        }

        return $this->subscription;
    }

    protected function getConfig()
    {
        $subscriberConfig = config('pubsub.subscribers.' . $this->subscriberName);
        if (empty($subscriberConfig)) {
            throw new WrongSubscriberException($this->subscriberName);
        }

        $connectionConfig = config('pubsub.connections.' . $subscriberConfig['connection']);
        if (empty($connectionConfig)) {
            throw new WrongConnectionException($subscriberConfig['connection']);
        }

        return [
            'subscriber' => $subscriberConfig['subscriber'],
            'connectionConfig' => $connectionConfig,
        ];
    }
}
