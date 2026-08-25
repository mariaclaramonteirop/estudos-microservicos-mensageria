<?php

namespace App\RabbitMQ\Publisher;

use App\RabbitMQ\Event\EventInterface;

interface PublisherInterface
{
    public function publish(EventInterface $event): void;
}