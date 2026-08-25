<?php

namespace App\Service;

use App\Entity\Order;
use App\RabbitMQ\Event\OrderCreatedEvent;
use App\Repository\OrderRepository;
use App\RabbitMQ\PublisherInterface;
class OrderService {
    public function __construct(
        private OrderRepository $repository,
        private PublisherInterface $publisher
    ) {
    }

    public function create(
        int $customerId,
        int $productId,
        int $quantity
    ): Order {

        $order = new Order(
            customerId: $customerId,
            productId: $productId,
            quantity: $quantity
        );

        $order = $this->repository->save($order);

        $event = new OrderCreatedEvent(
            orderId: $order->getId(),
            customerId: $order->getCustomerId(),
            productId: $order->getProductId(),
            quantity: $order->getQuantity()
        );

        $this->publisher->publish($event);

        return $order;
    }

}