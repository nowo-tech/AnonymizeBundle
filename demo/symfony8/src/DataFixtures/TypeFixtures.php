<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Type;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class TypeFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $types = [
            ['name' => 'HR', 'description' => 'Human Resources'],
            ['name' => 'HR Management', 'description' => 'HR Management Department'],
            ['name' => 'Sales', 'description' => 'Sales Department'],
            ['name' => 'IT', 'description' => 'Information Technology'],
            ['name' => 'Marketing', 'description' => 'Marketing Department'],
            ['name' => 'Finance', 'description' => 'Finance Department'],
            ['name' => 'Operations', 'description' => 'Operations Department'],
        ];

        foreach ($types as $typeData) {
            $type = new Type();
            $type->setName($typeData['name']);
            $type->setDescription($typeData['description']);

            $manager->persist($type);
            // Store reference for use in OrderFixtures
            $this->addReference('type_' . strtolower(str_replace(' ', '_', $typeData['name'])), $type);
        }

        $manager->flush();
    }
}
