<?php

namespace App\DAO;

use App\Entity\Product;
use App\Repository\ProductRepository;

class ProductDAO implements ProductRepository
{

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    public function findById(int $id): ?Product
    {
        $sql = "SELECT * FROM products WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        return new Product(
            $row['id'],
            $row['name'],
            $row['description'],
            $row['price']
        );
    }

    public function findAll(): array
    {
        $sql = "SELECT * FROM products";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $products = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $products[] = new Product(
                $row['id'],
                $row['name'],
                $row['description'],
                $row['price']
            );
        }
        return $products;
    }

    public function save(Product $product): void
    {
        $sql = "INSERT INTO products (name, description, price) VALUES (:name, :description, :price)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'price' => $product->getPrice()
        ]);
        $product->setId($this->pdo->lastInsertId());
    }

    public function delete(int $id): void
    {
        $sql = "DELETE FROM products WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        if ($stmt->rowCount() === 0) {
            throw new \Exception("Product with ID $id not found.");
        }
        return true;
    }

    public function update(Product $product): void
    {
        $sql = "UPDATE products SET name = :name, description = :description, price = :price WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $product->getId(),
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'price' => $product->getPrice()
        ]);

        if ($stmt->rowCount() === 0) {
            throw new \Exception("Product with ID {$product->getId()} not found or no changes made.");
        }
        return true;
    }

    public function findByRestrictions(array $restrictions): array
    {
        $sql = "SELECT * FROM products WHERE 1=1";
        $params = [];

        foreach ($restrictions as $key => $value) {
            $sql .= " AND $key = :$key";
            $params[$key] = $value;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $products = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $products[] = new Product(
                $row['id'],
                $row['name'],
                $row['description'],
                $row['price']
            );
        }

        return $products;
    }

    public function decreaseStock(int $productId, int $quantity): void
    {
        $sql = "UPDATE products SET stock = stock - :quantity WHERE id = :id AND stock >= :quantity";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $productId,
            'quantity' => $quantity
        ]);

        if ($stmt->rowCount() === 0) {
            throw new \Exception("Product with ID {$productId} not found or insufficient stock.");
        }
    }
}