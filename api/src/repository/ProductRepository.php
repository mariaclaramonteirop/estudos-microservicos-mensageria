<?php

namespace App\Repository;

use App\Entity\Product;

interface ProductRepository
{
    public function findById(int $id): ?Product;
    public function findAll(): array;
    public function save(Product $product): void;
    public function delete(int $id): void;
    public function update(Product $product): void;
    public function findByRestrictions(array $restrictions): array;
    public function decreaseStock(int $productId, int $quantity): void;
}