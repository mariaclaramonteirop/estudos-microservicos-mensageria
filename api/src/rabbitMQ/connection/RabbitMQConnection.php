<?php

namespace App\RabbitMQ\Connection;

use PhpAmqpLib\Connection\AMQPStreamConnection;

class RabbitMQConnection
{
    private AMQPStreamConnection $connection;

    public function __construct()
    {
        $this->connection = new AMQPStreamConnection(
            $_ENV['RABBITMQ_HOST'],
            (int) $_ENV['RABBITMQ_PORT'],
            $_ENV['RABBITMQ_USER'],
            $_ENV['RABBITMQ_PASSWORD']
        );
    }

    public function getConnection(): AMQPStreamConnection
    {
        return $this->connection;
    }

    public function createChannel()
    {
        return $this->connection->channel();
    }

    public function close(): void
    {
        $this->connection->close();
    }
}