<?php

namespace App\DAO;

use App\Entity\Order;
use App\Repository\OrderRepository;

class OrderDAO implements OrderRepository
{
    public function findById(int $id): ?Order
    {
        $sql = "SELECT * FROM orders WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return new Order(
            $row['id'],
            $row['customer_id'],
            $row['product_id'],
            $row['quantity']
        );
    }

    public function findAll(): array
    {
        $sql = "SELECT * FROM orders";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $orders = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $orders[] = new Order(
                $row['id'],
                $row['customer_id'],
                $row['product_id'],
                $row['quantity']
            );
        }
        return $orders;
    }

    public function save(Order $order): void
    {
        $sql = "INSERT INTO orders (customer_id, product_id, quantity) VALUES (:customer_id, :product_id, :quantity)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'customer_id' => $order->getCustomerId(),
            'product_id' => $order->getProductId(),
            'quantity' => $order->getQuantity()
        ]);
        $order->setId($this->pdo->lastInsertId());
    }

    public function delete(int $id): void
    {
        $sql = "DELETE FROM orders WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        if ($stmt->rowCount() === 0) {
            throw new \Exception("Order with ID $id not found.");
        }
    }

    public function update(Order $order): void
    {
        $sql = "UPDATE orders SET customer_id = :customer_id, product_id = :product_id, quantity = :quantity WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'customer_id' => $order->getCustomerId(),
            'product_id' => $order->getProductId(),
            'quantity' => $order->getQuantity(),
            'id' => $order->getId()
        ]);

        if ($stmt->rowCount() === 0) {
            throw new \Exception("Order with ID {$order->getId()} not found or no changes made.");
        }
        return true;
    }

    public function findByRestrictions(array $restrictions): array
    {
        $sql = "SELECT * FROM orders where 1=1"; // Base query to allow dynamic conditions
        $params = [];

        foreach ($restrictions as $key => $value) {
            $sql .= " AND $key = :$key";
            $params[$key] = $value;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $orders = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $orders[] = new Order(
                $row['id'],
                $row['customer_id'],
                $row['product_id'],
                $row['quantity']
            );
        }

        return $orders;
    }
}