<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\FakerTypeExample;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class FakerTypeExampleFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $examples = [
            [
                'email'          => 'user1@example.com',
                'legalId'        => '12345678A',
                'signature'      => '<div><p><strong>User One</strong><br>Developer</p></div>',
                'username'       => 'user1@example.com(15)',
                'sensitiveNotes' => 'Sensitive notes for user 1',
                'name'           => 'User One',
                'status'         => 'active',
            ],
            [
                'email'          => 'user2@example.com',
                'legalId'        => '87654321B',
                'signature'      => '<div><p><strong>User Two</strong><br>Manager</p></div>',
                'username'       => 'user2@example.com(42)',
                'sensitiveNotes' => 'Sensitive notes for user 2',
                'name'           => 'User Two',
                'status'         => 'inactive',
            ],
            [
                'email'          => 'user3@example.com',
                'legalId'        => null,  // Will be preserved with preserve_null
                'signature'      => '<div><p><strong>User Three</strong><br>Designer</p></div>',
                'username'       => 'user3@example.com(7)',
                'sensitiveNotes' => 'Sensitive notes for user 3',
                'name'           => 'User Three',
                'status'         => 'pending',
            ],
            [
                'email'          => 'user4@example.com',
                'legalId'        => '11223344C',
                'signature'      => null,
                'username'       => 'user4@example.com(99)',
                'sensitiveNotes' => 'Sensitive notes for user 4',
                'name'           => 'User Four',
                'status'         => 'active',
            ],
            [
                'email'          => 'user5@example.com',
                'legalId'        => '55667788D',
                'signature'      => '<div><p><strong>User Five</strong><br>CEO</p></div>',
                'username'       => 'user5@example.com(123)',
                'sensitiveNotes' => null,  // Will be set to null by null faker
                'name'           => 'User Five',
                'status'         => 'unknown',  // Not in map -> anonymized to default 'status_unknown'
            ],
        ];

        foreach ($examples as $exampleData) {
            $example = new FakerTypeExample();
            $example->setEmail($exampleData['email']);
            $example->setLegalId($exampleData['legalId']);
            $example->setSignature($exampleData['signature']);
            $example->setUsername($exampleData['username']);
            $example->setSensitiveNotes($exampleData['sensitiveNotes']);
            $example->setName($exampleData['name']);
            $example->setStatus($exampleData['status']);
            $example->setAnonymized(false);

            $manager->persist($example);
        }

        $manager->flush();
    }
}
