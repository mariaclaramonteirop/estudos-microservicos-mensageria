<?php

namespace App\DAO;

use App\Entity\Costumer;
use App\Repository\CostumerRepository;

class CostumerDAO implements CostumerRepository
{
    public function findById(int $id): ?Costumer
    {
        // Implementation to find a costumer by ID
    }

    public function findAll(): array
    {
        // Implementation to find all costumers
    }

    public function save(Costumer $costumer): void
    {
        // Implementation to save a costumer
    }

    public function delete(int $id): void
    {
        // Implementation to delete a costumer by ID
    }

    public function update(Costumer $costumer): void
    {
        // Implementation to update a costumer
    }

    public function findByRestrictions(array $restrictions): array
    {
        // Implementation to find costumers by restrictions
    }
}