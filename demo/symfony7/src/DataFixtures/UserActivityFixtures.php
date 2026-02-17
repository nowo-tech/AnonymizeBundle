<?php

declare(strict_types=1);

namespace App\DataFixtures;

use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use MongoDB\Client;

use function sprintf;

/**
 * UserActivity fixtures for MongoDB.
 *
 * This fixture loads sample data into MongoDB for the UserActivity collection.
 * It uses the MongoDB PHP extension directly since Doctrine ODM is not yet configured.
 *
 * To use this fixture when MongoDB ODM support is added:
 * 1. Install doctrine/mongodb-odm-bundle
 * 2. Configure Doctrine ODM
 * 3. Update this fixture to use DocumentManager instead of MongoDB Client
 * 4. Uncomment the UserActivity document class
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class UserActivityFixtures extends Fixture
{
    private const COLLECTION_NAME = 'user_activities';

    /**
     * Load sample user activity data into MongoDB.
     *
     * Note: This fixture uses a shell script (load-fixtures.js) executed via mongosh
     * since Doctrine ODM is not yet configured. The script is executed automatically
     * by the entrypoint.sh script when the container starts.
     *
     * When MongoDB ODM support is added, this fixture should be updated to use
     * DocumentManager instead of the shell script approach.
     */
    public function load(ObjectManager $manager): void
    {
        // MongoDB fixtures are loaded via shell script (load-fixtures.js) in entrypoint.sh
        // This method is kept for compatibility with DoctrineFixturesBundle
        // but the actual loading happens via mongosh script

        // TODO: When MongoDB ODM is configured, update this to use DocumentManager:
        // $activities = $this->getSampleActivities();
        // foreach ($activities as $activityData) {
        //     $activity = new UserActivity();
        //     // ... set properties ...
        //     $manager->persist($activity);
        // }
        // $manager->flush();
    }

    /**
     * Get sample user activities data.
     */
    private function getSampleActivities(): array
    {
        $actions    = ['login', 'logout', 'view_page', 'update_profile', 'create_order', 'cancel_order', 'add_to_cart', 'remove_from_cart'];
        $activities = [];
        $now        = new DateTime();

        // Generate 30 sample activities
        for ($i = 1; $i <= 30; ++$i) {
            $timestamp = clone $now;
            $timestamp->modify(sprintf('-%d days', rand(0, 365)));
            $timestamp->modify(sprintf('-%d hours', rand(0, 23)));
            $timestamp->modify(sprintf('-%d minutes', rand(0, 59)));

            $activities[] = [
                'userEmail' => sprintf('user%d@example.com', $i),
                'userName'  => sprintf('User %d', $i),
                'ipAddress' => sprintf('%d.%d.%d.%d', rand(1, 255), rand(1, 255), rand(1, 255), rand(1, 255)),
                'action'    => $actions[array_rand($actions)],
                'timestamp' => new \MongoDB\BSON\UTCDateTime($timestamp->getTimestamp() * 1000),
                'metadata'  => [
                    'userAgent' => sprintf('Mozilla/5.0 (Browser %d)', $i),
                    'sessionId' => sprintf('session_%s', bin2hex(random_bytes(8))),
                    'referrer'  => $i % 2 === 0 ? 'https://example.com' : null,
                ],
            ];
        }

        return $activities;
    }
}
