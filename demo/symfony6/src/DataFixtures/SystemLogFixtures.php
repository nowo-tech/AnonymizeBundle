<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\SystemLog;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class SystemLogFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $logs = [
            [
                'sessionId'          => '550e8400-e29b-41d4-a716-446655440000',
                'ipAddress'          => '192.168.1.100',
                'macAddress'         => '00:1b:44:11:3a:b7',
                'apiKey'             => 'secret_api_key_12345',
                'tokenHash'          => 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3',
                'location'           => '40.4168,-3.7038',
                'themeColor'         => '#FF5733',
                'isActive'           => true,
                'score'              => '85.50',
                'logFile'            => 'logs/application.log',
                'metadata'           => '{"user_id": 123, "action": "login", "timestamp": "2024-01-15T10:30:00Z"}',
                'description'        => 'User successfully logged into the system and accessed the dashboard.',
                'logLevel'           => 'info',
                'countryCode'        => 'ES',
                'languageCode'       => 'es',
                'createdAt'          => new DateTime('-1 day'),
                'userIdHash'         => 'user123',
                'processStatus'      => 'completed',
                'dataClassification' => 'SENSITIVE',
            ],
            [
                'sessionId'          => '550e8400-e29b-41d4-a716-446655440001',
                'ipAddress'          => '10.0.0.50',
                'macAddress'         => 'aa:bb:cc:dd:ee:ff',
                'apiKey'             => 'another_secret_key',
                'tokenHash'          => 'b665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae4',
                'location'           => '41.3851,2.1734',
                'themeColor'         => '#33FF57',
                'isActive'           => false,
                'score'              => '92.75',
                'logFile'            => 'logs/error.log',
                'metadata'           => '{"error_code": 500, "message": "Internal server error", "stack_trace": "..."}',
                'description'        => 'An error occurred while processing the payment request. The transaction was not completed.',
                'logLevel'           => 'error',
                'countryCode'        => 'FR',
                'languageCode'       => 'fr',
                'createdAt'          => new DateTime('-2 days'),
                'userIdHash'         => 'user456',
                'processStatus'      => 'failed',
                'dataClassification' => 'CONFIDENTIAL',
            ],
            [
                'sessionId'          => '550e8400-e29b-41d4-a716-446655440002',
                'ipAddress'          => '172.16.0.10',
                'macAddress'         => '11:22:33:44:55:66',
                'apiKey'             => 'test_api_key_789',
                'tokenHash'          => 'c665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae5',
                'location'           => '51.5074,-0.1278',
                'themeColor'         => '#3357FF',
                'isActive'           => true,
                'score'              => '78.25',
                'logFile'            => 'logs/debug.log',
                'metadata'           => '{"debug_level": 3, "component": "authentication", "details": "..."}',
                'description'        => 'Debug information for authentication module. Checking user credentials and permissions.',
                'logLevel'           => 'debug',
                'countryCode'        => 'GB',
                'languageCode'       => 'en',
                'createdAt'          => new DateTime('-3 days'),
                'userIdHash'         => 'user789',
                'processStatus'      => 'processing',
                'dataClassification' => 'PUBLIC',
            ],
            [
                'sessionId'          => '550e8400-e29b-41d4-a716-446655440003',
                'ipAddress'          => '203.0.113.1',
                'macAddress'         => 'ff:ee:dd:cc:bb:aa',
                'apiKey'             => 'production_key_xyz',
                'tokenHash'          => 'd665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae6',
                'location'           => '48.8566,2.3522',
                'themeColor'         => '#FF33F5',
                'isActive'           => true,
                'score'              => '95.00',
                'logFile'            => 'logs/warning.log',
                'metadata'           => '{"warning_type": "deprecation", "affected_component": "legacy_api", "recommendation": "migrate"}',
                'description'        => 'Warning: A deprecated API endpoint was accessed. Please migrate to the new version as soon as possible.',
                'logLevel'           => 'warning',
                'countryCode'        => 'DE',
                'languageCode'       => 'de',
                'createdAt'          => new DateTime('-4 days'),
                'userIdHash'         => 'user012',
                'processStatus'      => 'pending',
                'dataClassification' => 'INTERNAL',
            ],
            [
                'sessionId'          => '550e8400-e29b-41d4-a716-446655440004',
                'ipAddress'          => '198.51.100.1',
                'macAddress'         => '12:34:56:78:90:ab',
                'apiKey'             => 'staging_key_abc',
                'tokenHash'          => 'e665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae7',
                'location'           => '40.7128,-74.0060',
                'themeColor'         => '#F5FF33',
                'isActive'           => false,
                'score'              => '67.33',
                'logFile'            => 'logs/info.log',
                'metadata'           => '{"event": "user_registration", "user_id": 999, "registration_method": "oauth"}',
                'description'        => 'New user registered successfully through OAuth authentication. Account verification email sent.',
                'logLevel'           => 'info',
                'countryCode'        => 'US',
                'languageCode'       => 'en',
                'createdAt'          => new DateTime('-5 days'),
                'userIdHash'         => 'user345',
                'processStatus'      => 'completed',
                'dataClassification' => 'RESTRICTED',
            ],
        ];

        foreach ($logs as $logData) {
            $log = new SystemLog();
            $log->setSessionId($logData['sessionId']);
            $log->setIpAddress($logData['ipAddress']);
            $log->setMacAddress($logData['macAddress']);
            $log->setApiKey($logData['apiKey']);
            $log->setTokenHash($logData['tokenHash']);
            $log->setLocation($logData['location']);
            $log->setThemeColor($logData['themeColor']);
            $log->setIsActive($logData['isActive']);
            $log->setScore($logData['score']);
            $log->setLogFile($logData['logFile']);
            $log->setMetadata($logData['metadata']);
            $log->setDescription($logData['description']);
            $log->setLogLevel($logData['logLevel']);
            $log->setCountryCode($logData['countryCode']);
            $log->setLanguageCode($logData['languageCode']);
            $log->setCreatedAt($logData['createdAt']);
            $log->setUserIdHash($logData['userIdHash']);
            $log->setProcessStatus($logData['processStatus']);
            $log->setDataClassification($logData['dataClassification']);

            $manager->persist($log);
        }

        $manager->flush();
    }
}
