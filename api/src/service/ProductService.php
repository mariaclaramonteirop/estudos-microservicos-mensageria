<?php

namespace App\Service;

use App\Entity\Product;
use App\Repository\ProductRepositoryInterface;

class ProductService
{
    public function __construct(
        private ProductRepositoryInterface $repository
    ) {
    }

    public function create(
        string $name,
        float $price,
        int $stock
    ): Product {
        $product = new Product(
            null,
            $name,
            $price,
            $stock
        );

        $this->validateProduct($product);

        return $this->repository->create($product);
    }

    private function validateProduct(
        Product $product
    ): void {
        if (empty($product->getName())) {
            throw new \InvalidArgumentException(
                'Product name is required'
            );
        }

        if ($product->getPrice() <= 0) {
            throw new \InvalidArgumentException(
                'Product price must be greater than zero'
            );
        }

        if ($product->getStock() < 0) {
            throw new \InvalidArgumentException(
                'Product stock cannot be negative'
            );
        }
    }
}