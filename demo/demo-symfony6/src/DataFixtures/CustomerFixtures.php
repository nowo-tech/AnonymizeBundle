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
            ['email' => 'customer1@example.com', 'name' => 'Customer One', 'status' => 'active'],
            ['email' => 'customer2@example.com', 'name' => 'Customer Two', 'status' => 'active'],
            ['email' => 'customer3@example.com', 'name' => 'Customer Three', 'status' => 'inactive'],
            ['email' => 'customer4@example.com', 'name' => 'Customer Four', 'status' => 'active'],
            ['email' => 'customer5@example.com', 'name' => 'Customer Five', 'status' => 'active'],
            ['email' => 'customer6@example.com', 'name' => 'Customer Six', 'status' => 'inactive'],
            ['email' => 'customer7@example.com', 'name' => 'Customer Seven', 'status' => 'active'],
            ['email' => 'customer8@example.com', 'name' => 'Customer Eight', 'status' => 'active'],
        ];

        foreach ($customers as $customerData) {
            $customer = new Customer();
            $customer->setEmail($customerData['email']);
            $customer->setName($customerData['name']);
            $customer->setStatus($customerData['status']);

            $manager->persist($customer);
        }

        $manager->flush();
    }
}
