<?php

namespace App\Repository;

use App\Entity\Costumer;

interface CostumerRepository
{
    public function findById(int $id): ?Costumer;
    public function findAll(): array;
    public function save(Costumer $costumer): void;
    public function delete(int $id): void;
    public function update(Costumer $costumer): void;
    public function findByRestrictions(array $restrictions): array;
    public function create(Costumer $costumer): Costumer;
}