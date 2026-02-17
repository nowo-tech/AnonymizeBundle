<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\CacheData;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CacheDataFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $cacheRecords = [
            [
                'cacheKey'   => 'user_profile_123',
                'cacheValue' => [
                    'name'        => 'John Doe',
                    'email'       => 'john@example.com',
                    'preferences' => ['theme' => 'dark', 'language' => 'en'],
                ],
                'expiresAt' => new DateTimeImmutable('+1 hour'),
            ],
            [
                'cacheKey'   => 'product_list_category_5',
                'cacheValue' => [
                    'products' => [
                        ['id' => 1, 'name' => 'Product A'],
                        ['id' => 2, 'name' => 'Product B'],
                    ],
                    'total' => 2,
                ],
                'expiresAt' => new DateTimeImmutable('+30 minutes'),
            ],
            [
                'cacheKey'   => 'session_data_abc123',
                'cacheValue' => [
                    'userId'       => 456,
                    'lastActivity' => '2025-01-20T10:30:00Z',
                    'cart'         => ['item1', 'item2'],
                ],
                'expiresAt' => new DateTimeImmutable('+2 hours'),
            ],
            [
                'cacheKey'   => 'api_response_users',
                'cacheValue' => [
                    'data' => [
                        ['id' => 1, 'name' => 'User 1'],
                        ['id' => 2, 'name' => 'User 2'],
                    ],
                    'timestamp' => '2025-01-20T12:00:00Z',
                ],
                'expiresAt' => new DateTimeImmutable('+1 day'),
            ],
            [
                'cacheKey'   => 'search_results_query_xyz',
                'cacheValue' => [
                    'query'   => 'test search',
                    'results' => ['result1', 'result2', 'result3'],
                    'count'   => 3,
                ],
                'expiresAt' => new DateTimeImmutable('+15 minutes'),
            ],
            [
                'cacheKey'   => 'config_settings',
                'cacheValue' => [
                    'app_name' => 'My App',
                    'version'  => '1.0.0',
                    'features' => ['feature1', 'feature2'],
                ],
                'expiresAt' => new DateTimeImmutable('+7 days'),
            ],
        ];

        foreach ($cacheRecords as $data) {
            $cacheData = new CacheData();
            $cacheData->setCacheKey($data['cacheKey']);
            $cacheData->setCacheValue($data['cacheValue']);
            $cacheData->setExpiresAt($data['expiresAt']);

            $manager->persist($cacheData);
        }

        $manager->flush();
    }
}
