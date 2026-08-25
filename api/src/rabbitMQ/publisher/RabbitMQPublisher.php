<?php

namespace App\RabbitMQ\Publisher;

use App\RabbitMQ\Connection\RabbitMQConnection;
use App\RabbitMQ\Event\EventInterface;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQPublisher implements PublisherInterface
{
    public function __construct(
        private RabbitMQConnection $connection
    ) {
    }

    public function publish(EventInterface $event): void
    {
        $channel = $this->connection->createChannel();

        $message = new AMQPMessage(
            json_encode($event->toArray()),
            [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
            ]
        );

        $channel->basic_publish(
            $message,
            'orders.exchange',
            $event->getName()
        );

        $channel->close();
    }
}