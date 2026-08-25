<?php

namespace App\Repository;

use App\Entity\Order;

interface OrderRepository
{
    public function findById(int $id): ?Order;
    public function findAll(): array;
    public function save(Order $order): void;
    public function delete(int $id): void;
    public function update(Order $order): void;
    public function findByRestrictions(array $restrictions): array;
    public function create(Order $order): Order;
}