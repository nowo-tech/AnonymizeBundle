<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\UserAccount;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserAccountFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $accounts = [
            [
                'email' => 'hola@pepe.com',
                'username' => 'hola@pepe.com(15)',
                'usernameCanonical' => 'hola@pepe.com(15)',
                'emailCanonical' => 'hola@pepe.com',
            ],
            [
                'email' => 'jane.smith@company.com',
                'username' => 'jane.smith@company.com(42)',
                'usernameCanonical' => 'jane.smith@company.com(42)',
                'emailCanonical' => 'jane.smith@company.com',
            ],
            [
                'email' => 'bob.wilson@startup.io',
                'username' => 'bob.wilson@startup.io(7)',
                'usernameCanonical' => 'bob.wilson@startup.io(7)',
                'emailCanonical' => 'bob.wilson@startup.io',
            ],
            [
                'email' => 'alice.brown@corp.com',
                'username' => 'alice.brown@corp.com(99)',
                'usernameCanonical' => 'alice.brown@corp.com(99)',
                'emailCanonical' => 'alice.brown@corp.com',
            ],
            [
                'email' => 'charlie.davis@agency.net',
                'username' => 'charlie.davis@agency.net(123)',
                'usernameCanonical' => 'charlie.davis@agency.net(123)',
                'emailCanonical' => 'charlie.davis@agency.net',
            ],
            [
                'email' => 'david.miller@consulting.com',
                'username' => 'david.miller@consulting.com(5)',
                'usernameCanonical' => 'david.miller@consulting.com(5)',
                'emailCanonical' => 'david.miller@consulting.com',
            ],
            [
                'email' => 'emma.jones@finance.bank',
                'username' => 'emma.jones@finance.bank(88)',
                'usernameCanonical' => 'emma.jones@finance.bank(88)',
                'emailCanonical' => 'emma.jones@finance.bank',
            ],
            [
                'email' => 'frank.taylor@legal.firm',
                'username' => 'frank.taylor@legal.firm(33)',
                'usernameCanonical' => 'frank.taylor@legal.firm(33)',
                'emailCanonical' => 'frank.taylor@legal.firm',
            ],
            [
                'email' => 'grace.lee@tech.startup',
                'username' => 'grace.lee@tech.startup(11)',
                'usernameCanonical' => 'grace.lee@tech.startup(11)',
                'emailCanonical' => 'grace.lee@tech.startup',
            ],
            [
                'email' => 'henry.moore@design.studio',
                'username' => 'henry.moore@design.studio(66)',
                'usernameCanonical' => 'henry.moore@design.studio(66)',
                'emailCanonical' => 'henry.moore@design.studio',
            ],
        ];

        foreach ($accounts as $accountData) {
            $account = new UserAccount();
            $account->setEmail($accountData['email']);
            $account->setUsername($accountData['username']);
            $account->setUsernameCanonical($accountData['usernameCanonical']);
            $account->setEmailCanonical($accountData['emailCanonical']);
            $account->setAnonymized(false);

            $manager->persist($account);
        }

        $manager->flush();
    }
}
