<?php

namespace App\Service;

use App\Entity\Customer;
use App\Repository\CustomerRepository;

class CustomerService
{
    public function __construct(
        private CustomerRepository $repository
    ) {
    }

    public function create(
        string $name,
        string $email,
        string $phone
    ): Customer {
        $customer = new Customer(
            null,
            $name,
            $email,
            $phone
        );

        $this->validateCustomer($customer);

        return $this->repository->create($customer);
    }

    private function validateCustomer(
        Customer $customer
    ): void {
        if (empty($customer->getName())) {
            throw new \InvalidArgumentException(
                'Name is required'
            );
        }

        if (!filter_var(
            $customer->getEmail(),
            FILTER_VALIDATE_EMAIL
        )) {
            throw new \InvalidArgumentException(
                'Invalid email'
            );
        }

        if (empty($customer->getPhone())) {
            throw new \InvalidArgumentException(
                'Phone is required'
            );
        }
    }
}