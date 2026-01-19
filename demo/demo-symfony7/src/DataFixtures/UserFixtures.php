<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $users = [
            ['email' => 'john.doe@example.com', 'firstName' => 'John', 'lastName' => 'Doe', 'age' => 30, 'phone' => '123456789', 'iban' => 'ES9121000418450200051332', 'creditCard' => '4532015112830366', 'status' => 'active'],
            ['email' => 'jane.smith@example.com', 'firstName' => 'Jane', 'lastName' => 'Smith', 'age' => 25, 'phone' => '987654321', 'iban' => 'ES9121000418450200051333', 'creditCard' => '4532015112830367', 'status' => 'active'],
            ['email' => 'bob.wilson@example.com', 'firstName' => 'Bob', 'lastName' => 'Wilson', 'age' => 40, 'phone' => '555123456', 'iban' => 'ES9121000418450200051334', 'creditCard' => '4532015112830368', 'status' => 'inactive'],
            ['email' => 'alice.brown@example.com', 'firstName' => 'Alice', 'lastName' => 'Brown', 'age' => 28, 'phone' => '111222333', 'iban' => 'ES9121000418450200051335', 'creditCard' => '4532015112830369', 'status' => 'active'],
            ['email' => 'charlie.davis@example.com', 'firstName' => 'Charlie', 'lastName' => 'Davis', 'age' => 35, 'phone' => '444555666', 'iban' => 'ES9121000418450200051336', 'creditCard' => '4532015112830370', 'status' => 'active'],
        ];

        foreach ($users as $userData) {
            $user = new User();
            $user->setEmail($userData['email']);
            $user->setFirstName($userData['firstName']);
            $user->setLastName($userData['lastName']);
            $user->setAge($userData['age']);
            $user->setPhone($userData['phone']);
            $user->setIban($userData['iban']);
            $user->setCreditCard($userData['creditCard']);
            $user->setStatus($userData['status']);

            $manager->persist($user);
        }

        $manager->flush();
    }
}
