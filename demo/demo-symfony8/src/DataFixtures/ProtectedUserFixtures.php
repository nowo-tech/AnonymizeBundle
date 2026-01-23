<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\ProtectedUser;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProtectedUserFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $users = [
            // Records that will be EXCLUDED from anonymization (ID <= 100)
            // These match excludePatterns: ['id' => '<=100']
            ['email' => 'user1@example.com', 'name' => 'User One', 'phone' => '+34611111111', 'role' => 'user', 'status' => 'active', 'address' => '123 Main St, Madrid'],
            ['email' => 'user2@example.com', 'name' => 'User Two', 'phone' => '+34622222222', 'role' => 'user', 'status' => 'active', 'address' => '456 Oak Ave, Barcelona'],
            ['email' => 'user3@example.com', 'name' => 'User Three', 'phone' => '+34633333333', 'role' => 'user', 'status' => 'active', 'address' => '789 Pine Rd, Valencia'],
            ['email' => 'user4@example.com', 'name' => 'User Four', 'phone' => '+34644444444', 'role' => 'user', 'status' => 'active', 'address' => '321 Elm St, Seville'],
            ['email' => 'user5@example.com', 'name' => 'User Five', 'phone' => '+34655555555', 'role' => 'user', 'status' => 'active', 'address' => '654 Maple Dr, Bilbao'],

            // Records that will be EXCLUDED from anonymization (email ends with @visitor.com)
            // These match excludePatterns: ['email' => '%@visitor.com']
            ['email' => 'visitor1@visitor.com', 'name' => 'Visitor One', 'phone' => '+34611111111', 'role' => 'visitor', 'status' => 'active', 'address' => '111 Visitor St'],
            ['email' => 'visitor2@visitor.com', 'name' => 'Visitor Two', 'phone' => '+34622222222', 'role' => 'visitor', 'status' => 'active', 'address' => '222 Visitor Ave'],
            ['email' => 'visitor3@visitor.com', 'name' => 'Visitor Three', 'phone' => '+34633333333', 'role' => 'visitor', 'status' => 'active', 'address' => '333 Visitor Rd'],

            // Records that will be EXCLUDED from anonymization (role = 'admin')
            // These match excludePatterns: ['role' => 'admin']
            ['email' => 'admin1@example.com', 'name' => 'Admin One', 'phone' => '+34611111111', 'role' => 'admin', 'status' => 'active', 'address' => '999 Admin Blvd'],
            ['email' => 'admin2@example.com', 'name' => 'Admin Two', 'phone' => '+34622222222', 'role' => 'admin', 'status' => 'active', 'address' => '888 Admin Way'],
            ['email' => 'admin3@example.com', 'name' => 'Admin Three', 'phone' => '+34633333333', 'role' => 'admin', 'status' => 'active', 'address' => '777 Admin Ln'],

            // Records that will be EXCLUDED from anonymization (status = 'archived' or 'deleted')
            // These match excludePatterns: ['status' => 'archived|deleted']
            ['email' => 'archived1@example.com', 'name' => 'Archived One', 'phone' => '+34611111111', 'role' => 'user', 'status' => 'archived', 'address' => '555 Archive St'],
            ['email' => 'archived2@example.com', 'name' => 'Archived Two', 'phone' => '+34622222222', 'role' => 'user', 'status' => 'archived', 'address' => '444 Archive Ave'],
            ['email' => 'deleted1@example.com', 'name' => 'Deleted One', 'phone' => '+34633333333', 'role' => 'user', 'status' => 'deleted', 'address' => '333 Delete Rd'],
            ['email' => 'deleted2@example.com', 'name' => 'Deleted Two', 'phone' => '+34644444444', 'role' => 'user', 'status' => 'deleted', 'address' => '222 Delete Dr'],

            // Records that will be ANONYMIZED (ID > 100, email NOT ending in @visitor.com, role != 'admin', status != 'archived|deleted')
            // These do NOT match any excludePatterns
            ['email' => 'regular101@example.com', 'name' => 'Regular User 101', 'phone' => '+34610110101', 'role' => 'user', 'status' => 'active', 'address' => '101 Regular St'],
            ['email' => 'regular102@example.com', 'name' => 'Regular User 102', 'phone' => '+34610210202', 'role' => 'user', 'status' => 'active', 'address' => '102 Regular Ave'],
            ['email' => 'regular103@example.com', 'name' => 'Regular User 103', 'phone' => '+34610310303', 'role' => 'user', 'status' => 'active', 'address' => '103 Regular Rd'],
            ['email' => 'regular104@example.com', 'name' => 'Regular User 104', 'phone' => '+34610410404', 'role' => 'user', 'status' => 'active', 'address' => '104 Regular Dr'],
            ['email' => 'regular105@example.com', 'name' => 'Regular User 105', 'phone' => '+34610510505', 'role' => 'user', 'status' => 'active', 'address' => '105 Regular Ln'],
            ['email' => 'regular106@example.com', 'name' => 'Regular User 106', 'phone' => '+34610610606', 'role' => 'user', 'status' => 'active', 'address' => '106 Regular Blvd'],
            ['email' => 'regular107@example.com', 'name' => 'Regular User 107', 'phone' => '+34610710707', 'role' => 'user', 'status' => 'active', 'address' => '107 Regular Way'],
            ['email' => 'regular108@example.com', 'name' => 'Regular User 108', 'phone' => '+34610810808', 'role' => 'user', 'status' => 'active', 'address' => '108 Regular St'],
            ['email' => 'regular109@example.com', 'name' => 'Regular User 109', 'phone' => '+34610910909', 'role' => 'user', 'status' => 'active', 'address' => '109 Regular Ave'],
            ['email' => 'regular110@example.com', 'name' => 'Regular User 110', 'phone' => '+34611011010', 'role' => 'user', 'status' => 'active', 'address' => '110 Regular Rd'],

            // Edge cases: Multiple exclusion patterns (will be excluded if ANY pattern matches)
            ['email' => 'admin101@visitor.com', 'name' => 'Admin Visitor', 'phone' => '+34611111111', 'role' => 'admin', 'status' => 'active', 'address' => 'Mixed Exclusion St'],
            // This record matches MULTIPLE excludePatterns:
            // - email ends with @visitor.com
            // - role = 'admin'
            // - id > 100 (but email and role patterns already exclude it)
        ];

        foreach ($users as $index => $userData) {
            $user = new ProtectedUser();
            $user->setEmail($userData['email']);
            $user->setName($userData['name']);
            $user->setPhone($userData['phone']);
            $user->setRole($userData['role']);
            $user->setStatus($userData['status']);
            $user->setAddress($userData['address']);
            $user->setAnonymized(false);

            $manager->persist($user);
        }

        $manager->flush();
    }
}
