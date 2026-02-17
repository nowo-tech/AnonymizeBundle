<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Service;

use Nowo\AnonymizeBundle\Service\EnvironmentProtectionService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Test case for EnvironmentProtectionService.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class EnvironmentProtectionServiceTest extends TestCase
{
    /**
     * Test that performChecks returns empty array for dev environment.
     */
    public function testPerformChecksReturnsEmptyForDevEnvironment(): void
    {
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag->method('get')
            ->willReturnCallback(static function ($key) {
                return match ($key) {
                    'kernel.environment' => 'dev',
                    'kernel.debug'       => true,
                    'kernel.project_dir' => sys_get_temp_dir(),
                    default              => null,
                };
            });

        $service = new EnvironmentProtectionService($parameterBag);
        $errors  = $service->performChecks();

        $this->assertIsArray($errors);
        $this->assertEmpty($errors);
    }

    /**
     * Test that performChecks returns empty array for test environment.
     */
    public function testPerformChecksReturnsEmptyForTestEnvironment(): void
    {
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag->method('get')
            ->willReturnCallback(static function ($key) {
                return match ($key) {
                    'kernel.environment' => 'test',
                    'kernel.debug'       => true,
                    'kernel.project_dir' => sys_get_temp_dir(),
                    default              => null,
                };
            });

        $service = new EnvironmentProtectionService($parameterBag);
        $errors  = $service->performChecks();

        $this->assertIsArray($errors);
        $this->assertEmpty($errors);
    }

    /**
     * Test that performChecks returns errors for prod environment.
     */
    public function testPerformChecksReturnsErrorsForProdEnvironment(): void
    {
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag->method('get')
            ->willReturnCallback(static function ($key) {
                return match ($key) {
                    'kernel.environment' => 'prod',
                    'kernel.debug'       => false,
                    'kernel.project_dir' => sys_get_temp_dir(),
                    default              => null,
                };
            });

        $service = new EnvironmentProtectionService($parameterBag);
        $errors  = $service->performChecks();

        $this->assertIsArray($errors);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Unsafe environment', $errors[0]);
    }

    /**
     * Test that performChecks detects production config file.
     */
    public function testPerformChecksDetectsProductionConfigFile(): void
    {
        $tempDir = sys_get_temp_dir() . '/anonymize_test_' . uniqid();
        mkdir($tempDir, 0o755, true);
        mkdir($tempDir . '/config', 0o755, true);
        mkdir($tempDir . '/config/packages', 0o755, true);
        mkdir($tempDir . '/config/packages/prod', 0o755, true);
        file_put_contents($tempDir . '/config/packages/prod/nowo_anonymize.yaml', 'test');

        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag->method('get')
            ->willReturnCallback(static function ($key) use ($tempDir) {
                return match ($key) {
                    'kernel.environment' => 'dev',
                    'kernel.debug'       => true,
                    'kernel.project_dir' => $tempDir,
                    default              => null,
                };
            });

        $service = new EnvironmentProtectionService($parameterBag);
        $errors  = $service->performChecks();

        // Cleanup
        unlink($tempDir . '/config/packages/prod/nowo_anonymize.yaml');
        rmdir($tempDir . '/config/packages/prod');
        rmdir($tempDir . '/config/packages');
        rmdir($tempDir . '/config');
        rmdir($tempDir);

        $this->assertIsArray($errors);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Production configuration file detected', $errors[0]);
    }

    /**
     * Test that performChecks detects bundle registered for production.
     */
    public function testPerformChecksDetectsBundleRegisteredForProduction(): void
    {
        $tempDir = sys_get_temp_dir() . '/anonymize_test_' . uniqid();
        mkdir($tempDir, 0o755, true);
        mkdir($tempDir . '/config', 0o755, true);

        $bundlesContent = <<<'PHP'
            <?php

            return [
                'Nowo\AnonymizeBundle\AnonymizeBundle' => ['all' => true, 'prod' => true],
            ];
            PHP;
        file_put_contents($tempDir . '/config/bundles.php', $bundlesContent);

        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag->method('get')
            ->willReturnCallback(static function ($key) use ($tempDir) {
                return match ($key) {
                    'kernel.environment' => 'dev',
                    'kernel.debug'       => true,
                    'kernel.project_dir' => $tempDir,
                    default              => null,
                };
            });

        $service = new EnvironmentProtectionService($parameterBag);
        $errors  = $service->performChecks();

        // Cleanup
        unlink($tempDir . '/config/bundles.php');
        rmdir($tempDir . '/config');
        rmdir($tempDir);

        $this->assertIsArray($errors);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Bundle is registered for production', $errors[0]);
    }

    /**
     * Test that getEnvironment returns correct environment.
     */
    public function testGetEnvironment(): void
    {
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag->method('get')
            ->with('kernel.environment')
            ->willReturn('dev');

        $service     = new EnvironmentProtectionService($parameterBag);
        $environment = $service->getEnvironment();

        $this->assertEquals('dev', $environment);
    }

    /**
     * Test that isSafeEnvironment returns true for dev.
     */
    public function testIsSafeEnvironmentReturnsTrueForDev(): void
    {
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag->method('get')
            ->with('kernel.environment')
            ->willReturn('dev');

        $service = new EnvironmentProtectionService($parameterBag);
        $isSafe  = $service->isSafeEnvironment();

        $this->assertTrue($isSafe);
    }

    /**
     * Test that isSafeEnvironment returns true for test.
     */
    public function testIsSafeEnvironmentReturnsTrueForTest(): void
    {
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag->method('get')
            ->with('kernel.environment')
            ->willReturn('test');

        $service = new EnvironmentProtectionService($parameterBag);
        $isSafe  = $service->isSafeEnvironment();

        $this->assertTrue($isSafe);
    }

    /**
     * Test that isSafeEnvironment returns false for prod.
     */
    public function testIsSafeEnvironmentReturnsFalseForProd(): void
    {
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag->method('get')
            ->with('kernel.environment')
            ->willReturn('prod');

        $service = new EnvironmentProtectionService($parameterBag);
        $isSafe  = $service->isSafeEnvironment();

        $this->assertFalse($isSafe);
    }

    /**
     * Test that performChecks handles missing bundles.php file.
     */
    public function testPerformChecksHandlesMissingBundlesFile(): void
    {
        $tempDir = sys_get_temp_dir() . '/anonymize_test_' . uniqid();
        mkdir($tempDir, 0o755, true);
        mkdir($tempDir . '/config', 0o755, true);

        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag->method('get')
            ->willReturnCallback(static function ($key) use ($tempDir) {
                return match ($key) {
                    'kernel.environment' => 'dev',
                    'kernel.debug'       => true,
                    'kernel.project_dir' => $tempDir,
                    default              => null,
                };
            });

        $service = new EnvironmentProtectionService($parameterBag);
        $errors  = $service->performChecks();

        // Cleanup
        rmdir($tempDir . '/config');
        rmdir($tempDir);

        $this->assertIsArray($errors);
        $this->assertEmpty($errors);
    }

    /**
     * Test that performChecks handles bundles.php with array format.
     */
    public function testPerformChecksHandlesBundlesArrayFormat(): void
    {
        $tempDir = sys_get_temp_dir() . '/anonymize_test_' . uniqid();
        mkdir($tempDir, 0o755, true);
        mkdir($tempDir . '/config', 0o755, true);

        $bundlesContent = <<<'PHP'
            <?php

            return [
                'Nowo\AnonymizeBundle\AnonymizeBundle' => ['all' => true],
            ];
            PHP;
        file_put_contents($tempDir . '/config/bundles.php', $bundlesContent);

        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag->method('get')
            ->willReturnCallback(static function ($key) use ($tempDir) {
                return match ($key) {
                    'kernel.environment' => 'dev',
                    'kernel.debug'       => true,
                    'kernel.project_dir' => $tempDir,
                    default              => null,
                };
            });

        $service = new EnvironmentProtectionService($parameterBag);
        $errors  = $service->performChecks();

        // Cleanup
        unlink($tempDir . '/config/bundles.php');
        rmdir($tempDir . '/config');
        rmdir($tempDir);

        $this->assertIsArray($errors);
        $this->assertEmpty($errors);
    }
}
