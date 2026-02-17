<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\EmailSubscription;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class EmailSubscriptionFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $subscriptions = [
            // ============================================
            // GROUP 1: test-domain.com emails (WILL BE ANONYMIZED)
            // ============================================
            ['email' => 'john.doe@test-domain.com', 'name' => 'John Doe', 'status' => 'active', 'backupEmail' => 'john.backup@example.com', 'subscribedAt' => new DateTime('-6 months'), 'unsubscribedAt' => null, 'source' => 'website', 'notes' => 'Regular subscriber'],
            ['email' => 'jane.smith@test-domain.com', 'name' => 'Jane Smith', 'status' => 'active', 'backupEmail' => 'jane.backup@company.com', 'subscribedAt' => new DateTime('-3 months'), 'unsubscribedAt' => null, 'source' => 'newsletter', 'notes' => 'Newsletter subscriber'],
            ['email' => 'bob.wilson@test-domain.com', 'name' => 'Bob Wilson', 'status' => 'inactive', 'backupEmail' => 'bob.backup@example.com', 'subscribedAt' => new DateTime('-1 year'), 'unsubscribedAt' => new DateTime('-2 months'), 'source' => 'promotion', 'notes' => 'Inactive user - should anonymize backup email and notes'],
            ['email' => 'alice.brown@test-domain.com', 'name' => 'Alice Brown', 'status' => 'unsubscribed', 'backupEmail' => 'alice.backup@test-domain.com', 'subscribedAt' => new DateTime('-8 months'), 'unsubscribedAt' => new DateTime('-1 month'), 'source' => 'partner', 'notes' => 'Unsubscribed user - should anonymize backup email, date and notes'],
            ['email' => 'charlie.davis@test-domain.com', 'name' => 'Charlie Davis', 'status' => 'active', 'backupEmail' => null, 'subscribedAt' => new DateTime('-4 months'), 'unsubscribedAt' => null, 'source' => 'website', 'notes' => null],

            // ============================================
            // GROUP 2: example.com emails (WILL BE ANONYMIZED)
            // ============================================
            ['email' => 'david.miller@example.com', 'name' => 'David Miller', 'status' => 'active', 'backupEmail' => 'david.backup@test-domain.com', 'subscribedAt' => new DateTime('-5 months'), 'unsubscribedAt' => null, 'source' => 'newsletter', 'notes' => 'Active subscriber'],
            ['email' => 'emma.jones@example.com', 'name' => 'Emma Jones', 'status' => 'inactive', 'backupEmail' => 'emma.backup@example.com', 'subscribedAt' => new DateTime('-10 months'), 'unsubscribedAt' => new DateTime('-3 months'), 'source' => 'promotion', 'notes' => 'Inactive - backup email and notes should be anonymized'],
            ['email' => 'frank.taylor@example.com', 'name' => 'Frank Taylor', 'status' => 'unsubscribed', 'backupEmail' => 'frank.backup@company.com', 'subscribedAt' => new DateTime('-11 months'), 'unsubscribedAt' => new DateTime('-5 months'), 'source' => 'website', 'notes' => 'Unsubscribed - all conditional fields should be anonymized'],
            ['email' => 'grace.anderson@example.com', 'name' => 'Grace Anderson', 'status' => 'active', 'backupEmail' => null, 'subscribedAt' => new DateTime('-2 months'), 'unsubscribedAt' => null, 'source' => 'partner', 'notes' => 'Active with no backup'],
            ['email' => 'henry.thomas@example.com', 'name' => 'Henry Thomas', 'status' => 'inactive', 'backupEmail' => null, 'subscribedAt' => new DateTime('-9 months'), 'unsubscribedAt' => new DateTime('-4 months'), 'source' => 'newsletter', 'notes' => 'Inactive without backup - notes should be anonymized'],

            // ============================================
            // GROUP 3: demo.local emails (WILL BE ANONYMIZED)
            // ============================================
            ['email' => 'isabella.martinez@demo.local', 'name' => 'Isabella Martinez', 'status' => 'active', 'backupEmail' => 'isabella.backup@demo.local', 'subscribedAt' => new DateTime('-7 months'), 'unsubscribedAt' => null, 'source' => 'website', 'notes' => 'Demo account active'],
            ['email' => 'james.rodriguez@demo.local', 'name' => 'James Rodriguez', 'status' => 'inactive', 'backupEmail' => 'james.backup@test-domain.com', 'subscribedAt' => new DateTime('-12 months'), 'unsubscribedAt' => new DateTime('-6 months'), 'source' => 'promotion', 'notes' => 'Demo inactive - backup and notes anonymized'],
            ['email' => 'karen.white@demo.local', 'name' => 'Karen White', 'status' => 'unsubscribed', 'backupEmail' => 'karen.backup@example.com', 'subscribedAt' => new DateTime('-13 months'), 'unsubscribedAt' => new DateTime('-7 months'), 'source' => 'partner', 'notes' => 'Demo unsubscribed - all conditional fields anonymized'],
            ['email' => 'lucas.harris@demo.local', 'name' => 'Lucas Harris', 'status' => 'active', 'backupEmail' => null, 'subscribedAt' => new DateTime('-1 month'), 'unsubscribedAt' => null, 'source' => 'newsletter', 'notes' => null],

            // ============================================
            // GROUP 4: Other domains (PRIMARY EMAIL NOT ANONYMIZED)
            // ============================================
            // Active users - primary email is not anonymized, other fields are according to patterns
            ['email' => 'maria.clark@company.com', 'name' => 'Maria Clark', 'status' => 'active', 'backupEmail' => 'maria.backup@company.com', 'subscribedAt' => new DateTime('-6 months'), 'unsubscribedAt' => null, 'source' => 'website', 'notes' => 'Company email active - email NOT anonymized'],
            ['email' => 'noah.lewis@company.com', 'name' => 'Noah Lewis', 'status' => 'active', 'backupEmail' => null, 'subscribedAt' => new DateTime('-4 months'), 'unsubscribedAt' => null, 'source' => 'newsletter', 'notes' => null],
            ['email' => 'olivia.walker@company.com', 'name' => 'Olivia Walker', 'status' => 'inactive', 'backupEmail' => 'olivia.backup@company.com', 'subscribedAt' => new DateTime('-8 months'), 'unsubscribedAt' => new DateTime('-2 months'), 'source' => 'promotion', 'notes' => 'Company email inactive - email NOT anonymized, but backup and notes YES'],
            ['email' => 'peter.hall@company.com', 'name' => 'Peter Hall', 'status' => 'unsubscribed', 'backupEmail' => 'peter.backup@company.com', 'subscribedAt' => new DateTime('-10 months'), 'unsubscribedAt' => new DateTime('-3 months'), 'source' => 'partner', 'notes' => 'Company email unsubscribed - email NOT anonymized, but backup, date and notes YES'],

            // Business.org domain
            ['email' => 'quinn.allen@business.org', 'name' => 'Quinn Allen', 'status' => 'active', 'backupEmail' => 'quinn.backup@business.org', 'subscribedAt' => new DateTime('-5 months'), 'unsubscribedAt' => null, 'source' => 'website', 'notes' => 'Business email active'],
            ['email' => 'rachel.young@business.org', 'name' => 'Rachel Young', 'status' => 'inactive', 'backupEmail' => 'rachel.backup@business.org', 'subscribedAt' => new DateTime('-9 months'), 'unsubscribedAt' => new DateTime('-4 months'), 'source' => 'newsletter', 'notes' => 'Business email inactive - backup and notes anonymized'],
            ['email' => 'samuel.king@business.org', 'name' => 'Samuel King', 'status' => 'unsubscribed', 'backupEmail' => 'samuel.backup@business.org', 'subscribedAt' => new DateTime('-11 months'), 'unsubscribedAt' => new DateTime('-5 months'), 'source' => 'promotion', 'notes' => 'Business email unsubscribed - backup, date and notes anonymized'],

            // Real-domain.net domain
            ['email' => 'tina.scott@real-domain.net', 'name' => 'Tina Scott', 'status' => 'active', 'backupEmail' => 'tina.backup@real-domain.net', 'subscribedAt' => new DateTime('-7 months'), 'unsubscribedAt' => null, 'source' => 'partner', 'notes' => 'Real domain active'],
            ['email' => 'victor.green@real-domain.net', 'name' => 'Victor Green', 'status' => 'inactive', 'backupEmail' => 'victor.backup@real-domain.net', 'subscribedAt' => new DateTime('-12 months'), 'unsubscribedAt' => new DateTime('-6 months'), 'source' => 'website', 'notes' => 'Real domain inactive'],
            ['email' => 'wendy.adams@real-domain.net', 'name' => 'Wendy Adams', 'status' => 'unsubscribed', 'backupEmail' => 'wendy.backup@real-domain.net', 'subscribedAt' => new DateTime('-13 months'), 'unsubscribedAt' => new DateTime('-8 months'), 'source' => 'newsletter', 'notes' => 'Real domain unsubscribed'],

            // Other domains
            ['email' => 'xavier.baker@other-domain.com', 'name' => 'Xavier Baker', 'status' => 'active', 'backupEmail' => 'xavier.backup@other-domain.com', 'subscribedAt' => new DateTime('-3 months'), 'unsubscribedAt' => null, 'source' => 'promotion', 'notes' => 'Other domain active'],
            ['email' => 'yolanda.cook@other-domain.com', 'name' => 'Yolanda Cook', 'status' => 'inactive', 'backupEmail' => 'yolanda.backup@other-domain.com', 'subscribedAt' => new DateTime('-8 months'), 'unsubscribedAt' => new DateTime('-2 months'), 'source' => 'partner', 'notes' => 'Other domain inactive'],
            ['email' => 'zachary.morris@other-domain.com', 'name' => 'Zachary Morris', 'status' => 'unsubscribed', 'backupEmail' => 'zachary.backup@other-domain.com', 'subscribedAt' => new DateTime('-10 months'), 'unsubscribedAt' => new DateTime('-4 months'), 'source' => 'website', 'notes' => 'Other domain unsubscribed'],

            // ============================================
            // GROUP 5: Special and edge cases
            // ============================================
            // Active with backup email - backup is not anonymized (only if inactive/unsubscribed)
            ['email' => 'anna.lee@test-domain.com', 'name' => 'Anna Lee', 'status' => 'active', 'backupEmail' => 'anna.backup@example.com', 'subscribedAt' => new DateTime('-5 months'), 'unsubscribedAt' => null, 'source' => 'newsletter', 'notes' => 'Active with backup - backup NOT anonymized'],

            // Inactive without backup email - only notes are anonymized
            ['email' => 'benjamin.wright@example.com', 'name' => 'Benjamin Wright', 'status' => 'inactive', 'backupEmail' => null, 'subscribedAt' => new DateTime('-9 months'), 'unsubscribedAt' => new DateTime('-3 months'), 'source' => 'promotion', 'notes' => 'Inactive without backup - notes anonymized'],

            // Unsubscribed without backup email - date and notes are anonymized
            ['email' => 'catherine.hill@demo.local', 'name' => 'Catherine Hill', 'status' => 'unsubscribed', 'backupEmail' => null, 'subscribedAt' => new DateTime('-11 months'), 'unsubscribedAt' => new DateTime('-5 months'), 'source' => 'partner', 'notes' => 'Unsubscribed without backup - date and notes anonymized'],

            // Active without notes - notes are not anonymized
            ['email' => 'daniel.ward@test-domain.com', 'name' => 'Daniel Ward', 'status' => 'active', 'backupEmail' => 'daniel.backup@example.com', 'subscribedAt' => new DateTime('-4 months'), 'unsubscribedAt' => null, 'source' => 'website', 'notes' => null],

            // Inactive without notes - notes do not exist, not anonymized
            ['email' => 'elizabeth.turner@example.com', 'name' => 'Elizabeth Turner', 'status' => 'inactive', 'backupEmail' => 'elizabeth.backup@demo.local', 'subscribedAt' => new DateTime('-8 months'), 'unsubscribedAt' => new DateTime('-2 months'), 'source' => 'newsletter', 'notes' => null],

            // Unsubscribed without notes - date is anonymized but notes do not exist
            ['email' => 'frederick.cooper@demo.local', 'name' => 'Frederick Cooper', 'status' => 'unsubscribed', 'backupEmail' => 'frederick.backup@test-domain.com', 'subscribedAt' => new DateTime('-10 months'), 'unsubscribedAt' => new DateTime('-4 months'), 'source' => 'promotion', 'notes' => null],

            // All possible sources
            ['email' => 'george.richardson@test-domain.com', 'name' => 'George Richardson', 'status' => 'active', 'backupEmail' => null, 'subscribedAt' => new DateTime('-6 months'), 'unsubscribedAt' => null, 'source' => 'website', 'notes' => 'Source: website'],
            ['email' => 'helen.cox@example.com', 'name' => 'Helen Cox', 'status' => 'active', 'backupEmail' => null, 'subscribedAt' => new DateTime('-5 months'), 'unsubscribedAt' => null, 'source' => 'newsletter', 'notes' => 'Source: newsletter'],
            ['email' => 'ian.howard@demo.local', 'name' => 'Ian Howard', 'status' => 'active', 'backupEmail' => null, 'subscribedAt' => new DateTime('-4 months'), 'unsubscribedAt' => null, 'source' => 'promotion', 'notes' => 'Source: promotion'],
            ['email' => 'julia.ward@test-domain.com', 'name' => 'Julia Ward', 'status' => 'active', 'backupEmail' => null, 'subscribedAt' => new DateTime('-3 months'), 'unsubscribedAt' => null, 'source' => 'partner', 'notes' => 'Source: partner'],

            // Different date ranges
            ['email' => 'kevin.torres@example.com', 'name' => 'Kevin Torres', 'status' => 'active', 'backupEmail' => null, 'subscribedAt' => new DateTime('-2 years'), 'unsubscribedAt' => null, 'source' => 'website', 'notes' => 'Subscribed 2 years ago'],
            ['email' => 'linda.peterson@demo.local', 'name' => 'Linda Peterson', 'status' => 'unsubscribed', 'backupEmail' => null, 'subscribedAt' => new DateTime('-1 year'), 'unsubscribedAt' => new DateTime('-1 month'), 'source' => 'newsletter', 'notes' => 'Unsubscribed 1 month ago'],
            ['email' => 'michael.gray@test-domain.com', 'name' => 'Michael Gray', 'status' => 'unsubscribed', 'backupEmail' => null, 'subscribedAt' => new DateTime('-6 months'), 'unsubscribedAt' => new DateTime('-1 week'), 'source' => 'promotion', 'notes' => 'Recently unsubscribed'],
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
