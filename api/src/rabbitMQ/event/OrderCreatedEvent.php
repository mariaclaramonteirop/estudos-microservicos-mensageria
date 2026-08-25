<?php

namespace App\RabbitMQ\Event;

class OrderCreatedEvent implements EventInterface
{
    public function __construct(
        private int $orderId,
        private int $customerId,
        private int $productId,
        private int $quantity
    ) {
    }

    public function getName(): string
    {
        return 'order.created';
    }

    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId,
            'customer_id' => $this->customerId,
            'product_id' => $this->productId,
            'quantity' => $this->quantity
        ];
    }
}