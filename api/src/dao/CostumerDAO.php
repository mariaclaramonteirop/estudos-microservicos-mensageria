<?php

namespace App\DAO;

use App\Entity\Costumer;
use App\Repository\CostumerRepository;

class CostumerDAO implements CostumerRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    public function findById(int $id): ?Costumer
    {
        $stmt = $this->pdo->prepare('SELECT * FROM costumers WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return new Costumer(
            $row['id'],
            $row['name'],
            $row['email'],
            $row['phone']
        );

    }

    public function findAll(): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM costumers');
        $stmt->execute();
        $costumers = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $costumers[] = new Costumer(
                $row['id'],
                $row['name'],
                $row['email'],
                $row['phone']
            );
        }
        return $costumers;
    }

    public function save(Costumer $costumer): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO costumers (name, email, phone) VALUES (:name, :email, :phone)');
        $stmt->execute([
            'name' => $costumer->getName(),
            'email' => $costumer->getEmail(),
            'phone' => $costumer->getPhone()
        ]);
        $costumer->setId($this->pdo->lastInsertId());

        return $costumer;
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM costumers WHERE id = :id');
        $stmt->execute(['id' => $id]);

        if ($stmt->rowCount() === 0) {
            throw new \Exception("Costumer with ID $id not found.");
        }

        return true;
    }

    public function update(Costumer $costumer): void
    {
        $stmt = $this->pdo->prepare('UPDATE costumers SET name = :name, email = :email, phone = :phone WHERE id = :id');
        $stmt->execute([
            'id' => $costumer->getId(),
            'name' => $costumer->getName(),
            'email' => $costumer->getEmail(),
            'phone' => $costumer->getPhone()
        ]);

        if ($stmt->rowCount() === 0) {
            throw new \Exception("Costumer with ID {$costumer->getId()} not found or no changes made.");
        }

        return true;
    }

    public function findByRestrictions(array $restrictions): array
    {
        $query = 'SELECT * FROM costumers WHERE 1=1';
        $params = [];

        foreach ($restrictions as $key => $value) {
            $query .= " AND $key = :$key";
            $params[$key] = $value;
        }

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);

        $costumers = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $costumers[] = new Costumer(
                $row['id'],
                $row['name'],
                $row['email'],
                $row['phone']
            );
        }

        return $costumers;
    }
}