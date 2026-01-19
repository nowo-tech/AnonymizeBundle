<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Customer;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CustomerFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $customers = [
            // Active customers (will be anonymized)
            ['email' => 'customer1@example.com', 'name' => 'Customer One', 'status' => 'active'],
            ['email' => 'customer2@example.com', 'name' => 'Customer Two', 'status' => 'active'],
            ['email' => 'customer3@example.com', 'name' => 'Customer Three', 'status' => 'active'],
            ['email' => 'customer4@example.com', 'name' => 'Customer Four', 'status' => 'active'],
            ['email' => 'customer5@example.com', 'name' => 'Customer Five', 'status' => 'active'],
            ['email' => 'customer6@example.com', 'name' => 'Customer Six', 'status' => 'active'],
            ['email' => 'customer7@example.com', 'name' => 'Customer Seven', 'status' => 'active'],
            ['email' => 'customer8@example.com', 'name' => 'Customer Eight', 'status' => 'active'],
            ['email' => 'customer9@example.com', 'name' => 'Customer Nine', 'status' => 'active'],
            ['email' => 'customer10@example.com', 'name' => 'Customer Ten', 'status' => 'active'],

            // Customers with ID <= 10 (will be excluded from anonymization)
            // Note: IDs 1-10 are excluded by excludePatterns: ['id' => '<=10']
            // So these won't be anonymized even if status is active

            // Active customers with ID > 10 (will be anonymized)
            ['email' => 'customer11@example.com', 'name' => 'Customer Eleven', 'status' => 'active'],
            ['email' => 'customer12@example.com', 'name' => 'Customer Twelve', 'status' => 'active'],
            ['email' => 'customer13@example.com', 'name' => 'Customer Thirteen', 'status' => 'active'],
            ['email' => 'customer14@example.com', 'name' => 'Customer Fourteen', 'status' => 'active'],
            ['email' => 'customer15@example.com', 'name' => 'Customer Fifteen', 'status' => 'active'],

            // Inactive customers (will NOT be anonymized)
            ['email' => 'customer16@example.com', 'name' => 'Customer Sixteen', 'status' => 'inactive'],
            ['email' => 'customer17@example.com', 'name' => 'Customer Seventeen', 'status' => 'inactive'],
            ['email' => 'customer18@example.com', 'name' => 'Customer Eighteen', 'status' => 'inactive'],
            ['email' => 'customer19@example.com', 'name' => 'Customer Nineteen', 'status' => 'inactive'],
            ['email' => 'customer20@example.com', 'name' => 'Customer Twenty', 'status' => 'inactive'],

            // More active customers for better pattern testing
            ['email' => 'customer21@example.com', 'name' => 'Customer Twenty-One', 'status' => 'active'],
            ['email' => 'customer22@example.com', 'name' => 'Customer Twenty-Two', 'status' => 'active'],
            ['email' => 'customer23@example.com', 'name' => 'Customer Twenty-Three', 'status' => 'active'],
            ['email' => 'customer24@example.com', 'name' => 'Customer Twenty-Four', 'status' => 'active'],
            ['email' => 'customer25@example.com', 'name' => 'Customer Twenty-Five', 'status' => 'active'],
        ];

        foreach ($customers as $index => $customerData) {
            $customer = new Customer();
            $customer->setEmail($customerData['email']);
            $customer->setName($customerData['name']);
            $customer->setStatus($customerData['status']);
            // Set initial reference code (will be anonymized later)
            $customer->setReferenceCode('CUST-' . str_pad((string) ($index + 1), 8, '0', STR_PAD_LEFT));

            $manager->persist($customer);
        }

        $manager->flush();
    }
}
