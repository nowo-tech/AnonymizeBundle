<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\EmailNotification;
use App\Entity\SmsNotification;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Fixtures for polymorphic notifications (STI example).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class NotificationFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $emails = [
            ['recipient' => 'user1@example.com', 'subject' => 'Welcome to the platform', 'body' => 'Thank you for signing up. We are glad to have you.'],
            ['recipient' => 'admin@company.com', 'subject' => 'Weekly report ready', 'body' => 'Your weekly analytics report is available for download.'],
            ['recipient' => 'support@service.org', 'subject' => 'Ticket #1234 resolved', 'body' => 'Your support request has been closed. Let us know if you need more help.'],
            ['recipient' => 'john.doe@test.com', 'subject' => 'Password reset', 'body' => 'Click the link below to reset your password. Link valid for 1 hour.'],
            ['recipient' => 'jane@demo.net', 'subject' => 'Order confirmation', 'body' => 'Your order #5678 has been received and is being processed.'],
        ];

        foreach ($emails as $data) {
            $n = new EmailNotification();
            $n->setRecipient($data['recipient']);
            $n->setSubject($data['subject']);
            $n->setBody($data['body']);
            $manager->persist($n);
        }

        $smsList = [
            ['recipient' => '+34600123456', 'message' => 'Your code is 847291. Valid for 10 minutes.'],
            ['recipient' => '+34912345678', 'message' => 'Appointment reminder: Tomorrow at 10:00.'],
            ['recipient' => '+34666777888', 'message' => 'Payment of 29.99 EUR received. Thank you.'],
            ['recipient' => '+34890123456', 'message' => 'Delivery out for delivery. ETA 14:00-16:00.'],
            ['recipient' => '+34611222333', 'message' => 'Your balance is low. Top up to avoid service interruption.'],
        ];

        foreach ($smsList as $data) {
            $n = new SmsNotification();
            $n->setRecipient($data['recipient']);
            $n->setMessage($data['message']);
            $manager->persist($n);
        }

        $manager->flush();
    }
}
