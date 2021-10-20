<?php

namespace AlanzEvo\GooglePubsub;

use AlanzEvo\GooglePubsub\Exceptions\WrongConnectionException;
use AlanzEvo\GooglePubsub\Exceptions\WrongDataException;
use AlanzEvo\GooglePubsub\Exceptions\WrongPublisherException;
use Illuminate\Contracts\Support\Arrayable;
use Google\Cloud\PubSub\PubSubClient;

class Publisher
{
    /**
     * @var string
     */
    protected $publisherName = '';

    /**
     * @param string $publisherName
     */
    public function __construct(string $publisherName = 'default')
    {
        $this->publisherName = $publisherName;
    }

    /**
     * Publish message to google pubsub
     * 
     * @param string $topic
     * @param array|Arrayable $data
     * @param array $attributes
     * @param string $orderingKey
     * @param array $options
     */
    public function publish(string $topic, $data, array $attributes = [], string $orderingKey = null, array $options = [])
    {
        $message['data'] = $this->convertToMessageData($data);

        if (count($attributes) > 0) {
            $message['attributes'] = $attributes;
        }

        if (!empty($orderingKey)) {
            $message['orderingKey'] = $orderingKey;
        }

        return $this->publishBatch($topic, [$message], $options);
    }

    /**
     * Publish message batch to google pubsub
     * 
     * @param string $topic
     * @param array $messages
     * @param array $options
     * @return array
     */
    public function publishBatch(string $topic, array $messages, array $options = []): array
    {
        $topic = $this->getPubSubTopic($topic);

        return $topic->publishBatch($messages, $options);
    }

    protected function convertToMessageData($data)
    {
        $isArray = is_array($data);
        $isArrayable = is_object($data) && method_exists($data, 'toArray');

        if ($isArray) {
            return json_encode($data);
        } elseif ($isArrayable) {
            return json_encode($data->toArray());
        } else {
            throw new WrongDataException();
        }

        return $data;
    }

    protected function getPubSubTopic(string $topic)
    {
        $config = $this->getConfig();
        $pubSub = app(PubSubClient::class, [
            'config' => $config['connectionConfig'],
        ]);

        $pubSubTopic = $pubSub->topic($topic);

        return $pubSubTopic;
    }

    protected function getConfig()
    {
        $subscriberConfig = config('pubsub.publishers.' . $this->publisherName);
        if (empty($subscriberConfig)) {
            throw new WrongPublisherException($this->publisherName);
        }

        $connectionConfig = config('pubsub.connections.' . $subscriberConfig['connection']);
        if (empty($connectionConfig)) {
            throw new WrongConnectionException($subscriberConfig['connection']);
        }

        return [
            'connectionConfig' => $connectionConfig,
        ];
    }
}
