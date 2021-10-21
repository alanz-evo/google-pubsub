<?php

namespace AlanzEvo\GooglePubsub;

use AlanzEvo\GooglePubsub\Exceptions\WrongConnectionException;
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
    protected $subscriptionId = null;

    /**
     * @var string
     */
    protected $connection = '';

    public function __construct(string $subscriptionId, string $connection = 'default')
    {
        $this->connection = $connection;
        $this->subscriptionId = $subscriptionId;
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
                'config' => $config,
            ]);

            $this->subscription = $pubSub->subscription($this->subscriptionId);
        }

        return $this->subscription;
    }

    protected function getConfig()
    {
        $config = config('pubsub.connections.' . $this->connection);
        if (is_null($config)) {
            throw new WrongConnectionException($this->connection);
        }

        return $config;
    }
}
