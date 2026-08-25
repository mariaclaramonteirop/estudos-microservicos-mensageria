<?php

namespace App\RabbitMQ;

use PhpAmqpLib\Channel\AMQPChannel;

class RabbitMQTopology
{
    public function __construct(private AMQPChannel $channel) {}

    public function declare(): void
    {
        $this->declareExchange();

        $this->declareNotificationQueue();
        $this->declareInventoryQueue();
    }

    private function declareExchange(): void
    {
        $this->channel->exchange_declare(
            'orders.exchange',
            'topic',
            false,
            true,
            false
        );
    }

    private function declareNotificationQueue(): void
    {
        $this->channel->queue_declare(
            'order.notification',
            false,
            true,
            false,
            false
        );

        $this->channel->queue_bind(
            'order.notification',
            'orders.exchange',
            'order.created'
        );
    }

    private function declareInventoryQueue(): void
    {
        $this->channel->queue_declare(
            'order.inventory',
            false,
            true,
            false,
            false
        );

        $this->channel->queue_bind(
            'order.inventory',
            'orders.exchange',
            'order.created'
        );
    }
}