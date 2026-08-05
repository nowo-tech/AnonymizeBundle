<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Unit\Service;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use Nowo\AnonymizeBundle\Service\EnvironmentProtectionService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * Test case for EnvironmentProtectionService.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class EnvironmentProtectionServiceTest extends TestCase
{
    private function backupEnv(string $key): mixed
    {
        return $_SERVER[$key] ?? null;
    }

    private function restoreEnv(string $key, mixed $backup): void
    {
        if ($backup === null) {
            unset($_SERVER[$key]);
        } else {
            $_SERVER[$key] = $backup;
        }
    }

    /**
     * Unsafe env (prod) fails.
     */
    public function testUnsafeProdEnvironmentFails(): void
    {
        $service = new EnvironmentProtectionService(new ParameterBag([
            'kernel.environment' => 'prod',
            'kernel.project_dir' => sys_get_temp_dir(),
        ]), []);

        $errors = $service->performChecks();

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Unsafe environment', $errors[0]);
    }

    /**
     * Blocked DSN substring matches DATABASE_URL.
     */
    public function testBlockedDsnSubstringMatchesDatabaseUrl(): void
    {
        $backup                  = $this->backupEnv('DATABASE_URL');
        $_SERVER['DATABASE_URL'] = 'mysql://user:pass@prod-db.example.com:3306/app';

        try {
            $service = new EnvironmentProtectionService(new ParameterBag([
                'kernel.environment' => 'dev',
                'kernel.project_dir' => sys_get_temp_dir(),
            ]), ['prod-db.example.com']);

            $errors = $service->performChecks();

            $this->assertNotEmpty($errors);
            $this->assertStringContainsString('Blocked connection marker', $errors[0]);
            $this->assertStringContainsString('DATABASE_URL', $errors[0]);
        } finally {
            $this->restoreEnv('DATABASE_URL', $backup);
        }
    }

    /**
     * Empty denylist skips DSN check (even if DATABASE_URL looks production-like).
     */
    public function testEmptyDenylistSkipsDsnCheck(): void
    {
        $backup                  = $this->backupEnv('DATABASE_URL');
        $_SERVER['DATABASE_URL'] = 'mysql://user:pass@prod-db.example.com:3306/app';

        try {
            $service = new EnvironmentProtectionService(new ParameterBag([
                'kernel.environment' => 'dev',
                'kernel.project_dir' => sys_get_temp_dir(),
            ]), []);

            $errors = $service->performChecks();

            $this->assertEmpty($errors);
        } finally {
            $this->restoreEnv('DATABASE_URL', $backup);
        }
    }

    /**
     * Safe dev env passes.
     */
    public function testSafeDevEnvironmentPasses(): void
    {
        $service = new EnvironmentProtectionService(new ParameterBag([
            'kernel.environment' => 'dev',
            'kernel.project_dir' => sys_get_temp_dir(),
        ]), []);

        $errors = $service->performChecks();

        $this->assertEmpty($errors);
    }

    /**
     * Safe test env passes.
     */
    public function testSafeTestEnvironmentPasses(): void
    {
        $service = new EnvironmentProtectionService(new ParameterBag([
            'kernel.environment' => 'test',
            'kernel.project_dir' => sys_get_temp_dir(),
        ]), []);

        $this->assertEmpty($service->performChecks());
    }

    /**
     * Detects production config file under project dir.
     */
    public function testPerformChecksDetectsProductionConfigFile(): void
    {
        $tempDir = sys_get_temp_dir() . '/anonymize_test_' . uniqid();
        mkdir($tempDir . '/config/packages/prod', 0o755, true);
        file_put_contents($tempDir . '/config/packages/prod/nowo_anonymize.yaml', 'test');

        try {
            $service = new EnvironmentProtectionService(new ParameterBag([
                'kernel.environment' => 'dev',
                'kernel.project_dir' => $tempDir,
            ]), []);

            $errors = $service->performChecks();

            $this->assertNotEmpty($errors);
            $this->assertStringContainsString('Production configuration file detected', $errors[0]);
        } finally {
            unlink($tempDir . '/config/packages/prod/nowo_anonymize.yaml');
            rmdir($tempDir . '/config/packages/prod');
            rmdir($tempDir . '/config/packages');
            rmdir($tempDir . '/config');
            rmdir($tempDir);
        }
    }

    /**
     * Detects bundle registered for production in bundles.php.
     */
    public function testPerformChecksDetectsBundleRegisteredForProduction(): void
    {
        $tempDir = sys_get_temp_dir() . '/anonymize_test_' . uniqid();
        mkdir($tempDir . '/config', 0o755, true);

        $bundlesContent = <<<'PHP'
            <?php

            return [
                'Nowo\AnonymizeBundle\AnonymizeBundle' => ['all' => true, 'prod' => true],
            ];
            PHP;
        file_put_contents($tempDir . '/config/bundles.php', $bundlesContent);

        try {
            $service = new EnvironmentProtectionService(new ParameterBag([
                'kernel.environment' => 'dev',
                'kernel.project_dir' => $tempDir,
            ]), []);

            $errors = $service->performChecks();

            $this->assertNotEmpty($errors);
            $this->assertStringContainsString('Bundle is registered for production', $errors[0]);
        } finally {
            unlink($tempDir . '/config/bundles.php');
            rmdir($tempDir . '/config');
            rmdir($tempDir);
        }
    }

    public function testGetEnvironment(): void
    {
        $service = new EnvironmentProtectionService(new ParameterBag([
            'kernel.environment' => 'dev',
            'kernel.project_dir' => sys_get_temp_dir(),
        ]), []);

        $this->assertSame('dev', $service->getEnvironment());
    }

    public function testIsSafeEnvironmentReturnsTrueForDev(): void
    {
        $service = new EnvironmentProtectionService(new ParameterBag([
            'kernel.environment' => 'dev',
            'kernel.project_dir' => sys_get_temp_dir(),
        ]), []);

        $this->assertTrue($service->isSafeEnvironment());
    }

    public function testIsSafeEnvironmentReturnsFalseForProd(): void
    {
        $service = new EnvironmentProtectionService(new ParameterBag([
            'kernel.environment' => 'prod',
            'kernel.project_dir' => sys_get_temp_dir(),
        ]), []);

        $this->assertFalse($service->isSafeEnvironment());
    }

    public function testPerformChecksHandlesMissingBundlesFile(): void
    {
        $tempDir = sys_get_temp_dir() . '/anonymize_test_' . uniqid();
        mkdir($tempDir . '/config', 0o755, true);

        try {
            $service = new EnvironmentProtectionService(new ParameterBag([
                'kernel.environment' => 'dev',
                'kernel.project_dir' => $tempDir,
            ]), []);

            $this->assertEmpty($service->performChecks());
        } finally {
            rmdir($tempDir . '/config');
            rmdir($tempDir);
        }
    }

    public function testEmptyBlockedSubstringIsIgnored(): void
    {
        $backup                  = $_SERVER['DATABASE_URL'] ?? null;
        $_SERVER['DATABASE_URL'] = 'mysql://user:pass@localhost/app';

        try {
            $service = new EnvironmentProtectionService(new ParameterBag([
                'kernel.environment' => 'dev',
                'kernel.project_dir' => sys_get_temp_dir(),
            ]), ['', 'not-a-match']);

            $this->assertEmpty($service->performChecks());
        } finally {
            if ($backup !== null) {
                $_SERVER['DATABASE_URL'] = $backup;
            } else {
                unset($_SERVER['DATABASE_URL']);
            }
        }
    }

    public function testGetEnvironmentReturnsEmptyWhenParameterMissing(): void
    {
        $service = new EnvironmentProtectionService(new ParameterBag([]), []);

        $this->assertSame('', $service->getEnvironment());
        $this->assertFalse($service->isSafeEnvironment());
    }

    /**
     * Blocked markers also match Doctrine connection url/host/dbname when registry is injected.
     */
    public function testBlockedDsnMatchesDoctrineConnectionParams(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('getParams')->willReturn([
            'url'    => 'mysql://user:pass@localhost:3306/app',
            'host'   => 'prod-db.internal',
            'dbname' => 'app',
        ]);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getConnections')->willReturn(['default' => $connection]);

        $service = new EnvironmentProtectionService(new ParameterBag([
            'kernel.environment' => 'dev',
            'kernel.project_dir' => sys_get_temp_dir(),
        ]), ['prod-db.internal'], $doctrine);

        $errors = $service->performChecks();

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Blocked connection marker', $errors[0]);
        $this->assertStringContainsString('Doctrine connection', $errors[0]);
    }

    public function testDoctrineAbsentKeepsEnvOnlyBehaviour(): void
    {
        $backup = $_SERVER['DATABASE_URL'] ?? null;
        unset($_SERVER['DATABASE_URL']);

        try {
            $service = new EnvironmentProtectionService(new ParameterBag([
                'kernel.environment' => 'dev',
                'kernel.project_dir' => sys_get_temp_dir(),
            ]), ['prod-db.internal'], null);

            $this->assertEmpty($service->performChecks());
        } finally {
            if ($backup !== null) {
                $_SERVER['DATABASE_URL'] = $backup;
            }
        }
    }
}
