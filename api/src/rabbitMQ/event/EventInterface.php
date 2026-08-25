<?php

namespace App\RabbitMQ\Event;

interface EventInterface
{
    public function getName(): string;
    public function toArray(): array;
}