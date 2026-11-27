<?php

namespace App\DAO;

use App\Entity\Order;
use App\Repository\OrderRepository;
use PDO;

class OrderDAO implements OrderRepository
{
    public function __construct(private PDO $pdo) {}

    // TODO: Adicionar tratamentos de excessões
    public function findById(int $id): ?Order
    {
        $sql = "SELECT * FROM orders WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function findAll(): array
    {
        $sql = "SELECT * FROM orders";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $orders = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $orders[] = $this->hydrate($row);
        }
        return $orders;
    }

    public function save(Order $order): void
    {
        $sql = "INSERT INTO orders (customer_id, product_id, quantity, unit_price, total, status) VALUES (:customer_id, :product_id, :quantity, :unit_price, :total, :status)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'customer_id' => $order->getCustomerId(),
            'product_id' => $order->getProductId(),
            'quantity' => $order->getQuantity(),
            'unit_price' => $order->getUnitPrice(),
            'total' => $order->getTotal(),
            'status' => $order->getStatus()
        ]);
        $order->setId((int) $this->pdo->lastInsertId());
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

        $allowed = ['id', 'customer_id', 'product_id', 'status'];
        foreach ($restrictions as $key => $value) {
            if (!in_array($key, $allowed, true)) {
                throw new \InvalidArgumentException("Unsupported order filter: $key");
            }
            $sql .= " AND $key = :$key";
            $params[$key] = $value;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $orders = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $orders[] = $this->hydrate($row);
        }

        return $orders;
    }

    public function create(Order $order): Order
    {
        $this->save($order);
        return $order;
    }

    private function hydrate(array $row): Order
    {
        return new Order((int) $row['id'], (int) $row['customer_id'], (int) $row['product_id'], (int) $row['quantity'], (float) $row['unit_price'], (float) $row['total'], $row['status']);
    }
}
