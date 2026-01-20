<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\EmailSubscription;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class EmailSubscriptionFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $subscriptions = [
            // Emails from test-domain.com (will be anonymized)
            ['email' => 'john.doe@test-domain.com', 'name' => 'John Doe', 'status' => 'active', 'backupEmail' => 'john.backup@example.com', 'subscribedAt' => new \DateTime('-6 months'), 'unsubscribedAt' => null, 'source' => 'website', 'notes' => 'Regular subscriber'],
            ['email' => 'jane.smith@test-domain.com', 'name' => 'Jane Smith', 'status' => 'active', 'backupEmail' => 'jane.backup@example.com', 'subscribedAt' => new \DateTime('-3 months'), 'unsubscribedAt' => null, 'source' => 'newsletter', 'notes' => 'Newsletter subscriber'],
            ['email' => 'bob.wilson@test-domain.com', 'name' => 'Bob Wilson', 'status' => 'inactive', 'backupEmail' => 'bob.backup@example.com', 'subscribedAt' => new \DateTime('-1 year'), 'unsubscribedAt' => new \DateTime('-2 months'), 'source' => 'promotion', 'notes' => 'Inactive user'],
            
            // Emails from example.com (will be anonymized)
            ['email' => 'alice.brown@example.com', 'name' => 'Alice Brown', 'status' => 'active', 'backupEmail' => 'alice.backup@test-domain.com', 'subscribedAt' => new \DateTime('-4 months'), 'unsubscribedAt' => null, 'source' => 'website', 'notes' => 'Active subscriber'],
            ['email' => 'charlie.davis@example.com', 'name' => 'Charlie Davis', 'status' => 'unsubscribed', 'backupEmail' => 'charlie.backup@test-domain.com', 'subscribedAt' => new \DateTime('-8 months'), 'unsubscribedAt' => new \DateTime('-1 month'), 'source' => 'partner', 'notes' => 'Unsubscribed user'],
            
            // Emails from demo.local (will be anonymized)
            ['email' => 'david.miller@demo.local', 'name' => 'David Miller', 'status' => 'active', 'backupEmail' => 'david.backup@example.com', 'subscribedAt' => new \DateTime('-2 months'), 'unsubscribedAt' => null, 'source' => 'newsletter', 'notes' => 'Demo account'],
            ['email' => 'emma.jones@demo.local', 'name' => 'Emma Jones', 'status' => 'inactive', 'backupEmail' => 'emma.backup@test-domain.com', 'subscribedAt' => new \DateTime('-10 months'), 'unsubscribedAt' => new \DateTime('-3 months'), 'source' => 'promotion', 'notes' => 'Inactive demo user'],
            
            // Emails from other domains (will NOT be anonymized - no pattern match)
            ['email' => 'frank.taylor@company.com', 'name' => 'Frank Taylor', 'status' => 'active', 'backupEmail' => 'frank.backup@company.com', 'subscribedAt' => new \DateTime('-5 months'), 'unsubscribedAt' => null, 'source' => 'website', 'notes' => 'Company email'],
            ['email' => 'grace.anderson@business.org', 'name' => 'Grace Anderson', 'status' => 'active', 'backupEmail' => 'grace.backup@business.org', 'subscribedAt' => new \DateTime('-7 months'), 'unsubscribedAt' => null, 'source' => 'newsletter', 'notes' => 'Business email'],
            ['email' => 'henry.thomas@real-domain.net', 'name' => 'Henry Thomas', 'status' => 'active', 'backupEmail' => 'henry.backup@real-domain.net', 'subscribedAt' => new \DateTime('-9 months'), 'unsubscribedAt' => null, 'source' => 'partner', 'notes' => 'Real domain email'],
            
            // Active users with backup emails that should be anonymized (status inactive/unsubscribed)
            ['email' => 'isabella.martinez@company.com', 'name' => 'Isabella Martinez', 'status' => 'inactive', 'backupEmail' => 'isabella.backup@company.com', 'subscribedAt' => new \DateTime('-1 year'), 'unsubscribedAt' => new \DateTime('-4 months'), 'source' => 'website', 'notes' => 'Inactive with backup'],
            ['email' => 'james.rodriguez@business.org', 'name' => 'James Rodriguez', 'status' => 'unsubscribed', 'backupEmail' => 'james.backup@business.org', 'subscribedAt' => new \DateTime('-11 months'), 'unsubscribedAt' => new \DateTime('-5 months'), 'source' => 'promotion', 'notes' => 'Unsubscribed with backup'],
        ];

        foreach ($subscriptions as $subData) {
            $subscription = new EmailSubscription();
            $subscription->setEmail($subData['email']);
            $subscription->setName($subData['name']);
            $subscription->setStatus($subData['status']);
            $subscription->setBackupEmail($subData['backupEmail']);
            $subscription->setSubscribedAt($subData['subscribedAt']);
            $subscription->setUnsubscribedAt($subData['unsubscribedAt']);
            $subscription->setSource($subData['source']);
            $subscription->setNotes($subData['notes']);

            $manager->persist($subscription);
        }

        $manager->flush();
    }
}
