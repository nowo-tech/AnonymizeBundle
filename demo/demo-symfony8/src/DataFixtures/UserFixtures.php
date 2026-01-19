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
            // Active users with all fields
            ['email' => 'john.doe@example.com', 'firstName' => 'John', 'lastName' => 'Doe', 'age' => 30, 'phone' => '+34612345678', 'iban' => 'ES9121000418450200051332', 'creditCard' => '4532015112830366', 'status' => 'active'],
            ['email' => 'jane.smith@example.com', 'firstName' => 'Jane', 'lastName' => 'Smith', 'age' => 25, 'phone' => '+34698765432', 'iban' => 'ES9121000418450200051333', 'creditCard' => '4532015112830367', 'status' => 'active'],
            ['email' => 'bob.wilson@example.com', 'firstName' => 'Bob', 'lastName' => 'Wilson', 'age' => 40, 'phone' => '+34655512345', 'iban' => 'ES9121000418450200051334', 'creditCard' => '4532015112830368', 'status' => 'active'],
            ['email' => 'alice.brown@example.com', 'firstName' => 'Alice', 'lastName' => 'Brown', 'age' => 28, 'phone' => '+34611122233', 'iban' => 'ES9121000418450200051335', 'creditCard' => '4532015112830369', 'status' => 'active'],
            ['email' => 'charlie.davis@example.com', 'firstName' => 'Charlie', 'lastName' => 'Davis', 'age' => 35, 'phone' => '+34644455566', 'iban' => 'ES9121000418450200051336', 'creditCard' => '4532015112830370', 'status' => 'active'],

            // Inactive users
            ['email' => 'david.miller@example.com', 'firstName' => 'David', 'lastName' => 'Miller', 'age' => 45, 'phone' => '+34677788899', 'iban' => 'ES9121000418450200051337', 'creditCard' => '4532015112830371', 'status' => 'inactive'],
            ['email' => 'emma.jones@example.com', 'firstName' => 'Emma', 'lastName' => 'Jones', 'age' => 22, 'phone' => '+34622233344', 'iban' => 'ES9121000418450200051338', 'creditCard' => '4532015112830372', 'status' => 'inactive'],

            // Users with missing optional fields (null values)
            ['email' => 'frank.taylor@example.com', 'firstName' => 'Frank', 'lastName' => 'Taylor', 'age' => 50, 'phone' => null, 'iban' => null, 'creditCard' => null, 'status' => 'active'],
            ['email' => 'grace.anderson@example.com', 'firstName' => 'Grace', 'lastName' => 'Anderson', 'age' => 33, 'phone' => '+34633344455', 'iban' => null, 'creditCard' => '4532015112830373', 'status' => 'active'],
            ['email' => 'henry.thomas@example.com', 'firstName' => 'Henry', 'lastName' => 'Thomas', 'age' => 29, 'phone' => null, 'iban' => 'ES9121000418450200051339', 'creditCard' => null, 'status' => 'active'],

            // Edge cases: very young and very old
            ['email' => 'isabella.martinez@example.com', 'firstName' => 'Isabella', 'lastName' => 'Martinez', 'age' => 18, 'phone' => '+34644455566', 'iban' => 'ES9121000418450200051340', 'creditCard' => '4532015112830374', 'status' => 'active'],
            ['email' => 'james.rodriguez@example.com', 'firstName' => 'James', 'lastName' => 'Rodriguez', 'age' => 100, 'phone' => '+34655566677', 'iban' => 'ES9121000418450200051341', 'creditCard' => '4532015112830375', 'status' => 'active'],

            // More active users for better testing
            ['email' => 'karen.white@example.com', 'firstName' => 'Karen', 'lastName' => 'White', 'age' => 38, 'phone' => '+34666677788', 'iban' => 'ES9121000418450200051342', 'creditCard' => '4532015112830376', 'status' => 'active'],
            ['email' => 'lucas.harris@example.com', 'firstName' => 'Lucas', 'lastName' => 'Harris', 'age' => 27, 'phone' => '+34677788899', 'iban' => 'ES9121000418450200051343', 'creditCard' => '4532015112830377', 'status' => 'active'],
            ['email' => 'maria.clark@example.com', 'firstName' => 'Maria', 'lastName' => 'Clark', 'age' => 31, 'phone' => '+34688899900', 'iban' => 'ES9121000418450200051344', 'creditCard' => '4532015112830378', 'status' => 'active'],
            ['email' => 'noah.lewis@example.com', 'firstName' => 'Noah', 'lastName' => 'Lewis', 'age' => 42, 'phone' => '+34699900011', 'iban' => 'ES9121000418450200051345', 'creditCard' => '4532015112830379', 'status' => 'active'],
            ['email' => 'olivia.walker@example.com', 'firstName' => 'Olivia', 'lastName' => 'Walker', 'age' => 26, 'phone' => '+34600011122', 'iban' => 'ES9121000418450200051346', 'creditCard' => '4532015112830380', 'status' => 'active'],
            ['email' => 'peter.hall@example.com', 'firstName' => 'Peter', 'lastName' => 'Hall', 'age' => 39, 'phone' => '+34611122233', 'iban' => 'ES9121000418450200051347', 'creditCard' => '4532015112830381', 'status' => 'active'],
            ['email' => 'quinn.allen@example.com', 'firstName' => 'Quinn', 'lastName' => 'Allen', 'age' => 24, 'phone' => '+34622233344', 'iban' => 'ES9121000418450200051348', 'creditCard' => '4532015112830382', 'status' => 'active'],
            ['email' => 'rachel.young@example.com', 'firstName' => 'Rachel', 'lastName' => 'Young', 'age' => 36, 'phone' => '+34633344455', 'iban' => 'ES9121000418450200051349', 'creditCard' => '4532015112830383', 'status' => 'active'],
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
