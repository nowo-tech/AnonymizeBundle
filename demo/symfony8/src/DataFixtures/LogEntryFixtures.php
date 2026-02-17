<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\LogEntry;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class LogEntryFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $logEntries = [
            [
                'message'   => 'User logged in successfully',
                'ipAddress' => '192.168.1.100',
                'loggedAt'  => new DateTimeImmutable('-1 day'),
            ],
            [
                'message'   => 'Failed login attempt detected',
                'ipAddress' => '10.0.0.50',
                'loggedAt'  => new DateTimeImmutable('-2 days'),
            ],
            [
                'message'   => 'Database query executed',
                'ipAddress' => '172.16.0.25',
                'loggedAt'  => new DateTimeImmutable('-3 days'),
            ],
            [
                'message'   => 'API endpoint accessed',
                'ipAddress' => '192.168.1.200',
                'loggedAt'  => new DateTimeImmutable('-4 days'),
            ],
            [
                'message'   => 'Error occurred during processing',
                'ipAddress' => '10.0.0.75',
                'loggedAt'  => new DateTimeImmutable('-5 days'),
            ],
            [
                'message'   => 'Cache cleared successfully',
                'ipAddress' => '172.16.0.100',
                'loggedAt'  => new DateTimeImmutable('-6 days'),
            ],
            [
                'message'   => 'User profile updated',
                'ipAddress' => '192.168.1.150',
                'loggedAt'  => new DateTimeImmutable('-7 days'),
            ],
            [
                'message'   => 'Payment transaction completed',
                'ipAddress' => '10.0.0.125',
                'loggedAt'  => new DateTimeImmutable('-8 days'),
            ],
            [
                'message'   => 'Email sent to user',
                'ipAddress' => '172.16.0.50',
                'loggedAt'  => new DateTimeImmutable('-9 days'),
            ],
            [
                'message'   => 'System backup completed',
                'ipAddress' => '192.168.1.250',
                'loggedAt'  => new DateTimeImmutable('-10 days'),
            ],
        ];

        foreach ($logEntries as $data) {
            $logEntry = new LogEntry();
            $logEntry->setMessage($data['message']);
            $logEntry->setIpAddress($data['ipAddress']);
            $logEntry->setLoggedAt($data['loggedAt']);

            $manager->persist($logEntry);
        }

        $manager->flush();
    }
}
