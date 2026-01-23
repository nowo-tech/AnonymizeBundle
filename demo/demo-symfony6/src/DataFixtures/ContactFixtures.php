<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Contact;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ContactFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $contacts = [
            [
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'phone' => '+34612345678',
                'legalId' => '12345678A',
                'address' => '123 Main Street, Madrid',
                'emailSignature' => '<div><p><strong>John Doe</strong><br>Developer</p></div>',
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane.smith@company.com',
                'phone' => null,  // null - will be preserved with preserve_null
                'legalId' => '87654321B',
                'address' => '456 Oak Avenue, Barcelona',
                'emailSignature' => null,  // null - will be preserved with preserve_null
            ],
            [
                'name' => 'Bob Wilson',
                'email' => 'bob.wilson@startup.io',
                'phone' => '+34655512345',
                'legalId' => null,  // null - will be preserved with preserve_null
                'address' => null,  // null - will be anonymized with nullable option (30% chance)
                'emailSignature' => '<div><p><strong>Bob Wilson</strong><br>CEO</p></div>',
            ],
            [
                'name' => 'Alice Brown',
                'email' => null,  // null - will be anonymized with nullable option (20% chance)
                'phone' => '+34611222333',
                'legalId' => '11223344C',
                'address' => '789 Pine Road, Valencia',
                'emailSignature' => '<div><p><strong>Alice Brown</strong><br>Manager</p></div>',
            ],
            [
                'name' => 'Charlie Davis',
                'email' => 'charlie.davis@agency.net',
                'phone' => null,  // null - will be preserved with preserve_null
                'legalId' => '55667788D',
                'address' => null,  // null - will be anonymized with nullable option (30% chance)
                'emailSignature' => null,  // null - will be preserved with preserve_null
            ],
            [
                'name' => 'David Miller',
                'email' => 'david.miller@consulting.com',
                'phone' => '+34677888999',
                'legalId' => null,  // null - will be preserved with preserve_null
                'address' => '321 Elm Street, Seville',
                'emailSignature' => '<div><p><strong>David Miller</strong><br>Consultant</p></div>',
            ],
            [
                'name' => 'Emma Jones',
                'email' => 'emma.jones@finance.bank',
                'phone' => '+34622933444',
                'legalId' => '99887766E',
                'address' => null,  // null - will be anonymized with nullable option (30% chance)
                'emailSignature' => '<div><p><strong>Emma Jones</strong><br>Advisor</p></div>',
            ],
            [
                'name' => 'Frank Taylor',
                'email' => null,  // null - will be anonymized with nullable option (20% chance)
                'phone' => null,  // null - will be preserved with preserve_null
                'legalId' => '44332211F',
                'address' => '654 Maple Drive, Bilbao',
                'emailSignature' => null,  // null - will be preserved with preserve_null
            ],
        ];

        foreach ($contacts as $contactData) {
            $contact = new Contact();
            $contact->setName($contactData['name']);
            $contact->setEmail($contactData['email']);
            $contact->setPhone($contactData['phone']);
            $contact->setLegalId($contactData['legalId']);
            $contact->setAddress($contactData['address']);
            $contact->setEmailSignature($contactData['emailSignature']);
            $contact->setAnonymized(false);

            $manager->persist($contact);
        }

        $manager->flush();
    }
}
