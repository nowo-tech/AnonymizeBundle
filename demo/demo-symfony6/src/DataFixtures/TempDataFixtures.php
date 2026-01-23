<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\TempData;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class TempDataFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $tempDataRecords = [
            [
                'email' => 'temp1@example.com',
                'name' => 'Temporary User 1',
                'phone' => '+1234567890',
            ],
            [
                'email' => 'temp2@example.com',
                'name' => 'Temporary User 2',
                'phone' => '+1234567891',
            ],
            [
                'email' => 'temp3@example.com',
                'name' => 'Temporary User 3',
                'phone' => '+1234567892',
            ],
            [
                'email' => 'temp4@example.com',
                'name' => 'Temporary User 4',
                'phone' => null,
            ],
            [
                'email' => 'temp5@example.com',
                'name' => 'Temporary User 5',
                'phone' => '+1234567894',
            ],
            [
                'email' => 'temp6@example.com',
                'name' => 'Temporary User 6',
                'phone' => '+1234567895',
            ],
            [
                'email' => 'temp7@example.com',
                'name' => 'Temporary User 7',
                'phone' => null,
            ],
            [
                'email' => 'temp8@example.com',
                'name' => 'Temporary User 8',
                'phone' => '+1234567897',
            ],
        ];

        foreach ($tempDataRecords as $index => $data) {
            $tempData = new TempData();
            $tempData->setEmail($data['email']);
            $tempData->setName($data['name']);
            $tempData->setPhone($data['phone']);
            $tempData->setCreatedAt(new \DateTimeImmutable(sprintf('-%d days', 10 - $index)));

            $manager->persist($tempData);
        }

        $manager->flush();
    }
}
