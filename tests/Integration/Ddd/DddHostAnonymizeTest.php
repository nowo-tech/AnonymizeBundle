<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Integration\Ddd;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use Nowo\AnonymizeBundle\Command\AnonymizeCommand;
use Nowo\AnonymizeBundle\Command\GenerateAnonymizedColumnCommand;
use Nowo\AnonymizeBundle\Faker\FakerFactory;
use Nowo\AnonymizeBundle\Metadata\ConfigAnonymizeMetadataProvider;
use Nowo\AnonymizeBundle\Service\AnonymizationHistoryService;
use Nowo\AnonymizeBundle\Service\AnonymizeService;
use Nowo\AnonymizeBundle\Service\EnvironmentProtectionService;
use Nowo\AnonymizeBundle\Service\PatternMatcher;
use Nowo\AnonymizeBundle\Service\PreFlightCheckService;
use Nowo\AnonymizeBundle\Tests\Integration\Ddd\Domain\ContactDetails;
use Nowo\AnonymizeBundle\Tests\Integration\Ddd\Domain\Patient;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

use const PHP_VERSION_ID;

/**
 * Since 1.5, the DDD path end to end: a plain-PHP domain mapped in XML (no bundle or Doctrine attribute, an XML
 * embeddable, an `anonymized` column without AnonymizableTrait) selected through `nowo_anonymize.entities`, run by
 * nowo:anonymize:run against a real Doctrine ORM + SQLite schema.
 */
final class DddHostAnonymizeTest extends TestCase
{
    private const ENTITIES = [
        Patient::class => [
            'exclude_patterns' => ['status' => 'keep'],
            'mark_anonymized'  => true,
            'properties'       => [
                'email'         => ['type' => 'email', 'weight' => 1],
                'fullName'      => ['type' => 'constant', 'options' => ['value' => 'Anonymous patient']],
                'contact.phone' => ['type' => 'constant', 'options' => ['value' => '000 000 000']],
            ],
        ],
    ];

    private EntityManager $em;

    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/anonymize_ddd_' . uniqid();
        mkdir($this->tempDir, 0o755, true);

        $config = new Configuration();
        $config->setMetadataDriverImpl(new SimplifiedXmlDriver([__DIR__ . '/config/doctrine' => 'Nowo\\AnonymizeBundle\\Tests\\Integration\\Ddd\\Domain']));
        if (PHP_VERSION_ID >= 80400) {
            $config->enableNativeLazyObjects(true);
        } else {
            $config->setProxyDir($this->tempDir . '/proxies');
            $config->setProxyNamespace('AnonymizeDddProxies');
            $config->setAutoGenerateProxyClasses(true);
        }

        $this->em = new EntityManager(DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config), $config);
        (new SchemaTool($this->em))->createSchema($this->em->getMetadataFactory()->getAllMetadata());

        $this->em->persist(new Patient('p-1', 'ana@example.test', 'Ana Real', 'active', new ContactDetails('+34 600 000 001', 'Madrid')));
        $this->em->persist(new Patient('p-2', 'kept@example.test', 'Kept Real', 'keep', new ContactDetails('+34 600 000 002', 'Bilbao')));
        $this->em->flush();
        $this->em->clear();
    }

    protected function tearDown(): void
    {
        $this->em->close();
        $this->removeDirectory($this->tempDir);
    }

    public function testConfigurationSelectsTheXmlMappedEntityWithoutAttributes(): void
    {
        $service  = $this->service();
        $entities = $service->getAnonymizableEntities($this->em);

        self::assertSame([Patient::class], array_keys($entities), 'the XML embeddable alone is not selected');
        self::assertSame(['status' => 'keep'], $entities[Patient::class]['attribute']->excludePatterns);

        $fields = array_column($service->getAnonymizableProperties(new ReflectionClass(Patient::class), $entities[Patient::class]['metadata']), 'fieldName');
        self::assertSame(['email', 'contact.phone', 'fullName'], $fields);
        self::assertTrue($service->marksAnonymized(new ReflectionClass(Patient::class)));
    }

    public function testRunAnonymizesFieldsAndEmbeddedValueAndSetsTheFlag(): void
    {
        [$exitCode, $out] = $this->runCommand();

        self::assertSame(0, $exitCode, $out);
        $rows = array_column($this->em->getConnection()->fetchAllAssociative('SELECT * FROM ddd_patient ORDER BY id'), null, 'id');

        self::assertNotSame('ana@example.test', $rows['p-1']['email']);
        self::assertStringContainsString('@', (string) $rows['p-1']['email']);
        self::assertSame('Anonymous patient', $rows['p-1']['full_name']);
        self::assertSame('000 000 000', $rows['p-1']['contact_phone']);
        self::assertSame('Madrid', $rows['p-1']['contact_city'], 'fields that are not selected are kept');
        self::assertSame(1, (int) $rows['p-1']['anonymized']);

        self::assertSame('kept@example.test', $rows['p-2']['email'], 'entity-level exclude pattern from configuration');
        self::assertSame(0, (int) $rows['p-2']['anonymized']);
    }

    public function testPreFlightReportsAConfiguredFieldThatDoesNotExist(): void
    {
        $entities                                           = self::ENTITIES;
        $entities[Patient::class]['properties']['nickname'] = ['type' => 'name'];
        $provider                                           = new ConfigAnonymizeMetadataProvider($entities);
        $service                                            = new AnonymizeService(new FakerFactory('en_US'), new PatternMatcher(), null, null, $provider);

        $errors = (new PreFlightCheckService(new FakerFactory('en_US'), $provider))->performChecks($this->em, $service->getAnonymizableEntities($this->em));

        self::assertCount(1, array_filter($errors, static fn (string $error): bool => str_contains($error, 'The anonymized field "nickname" configured for "' . Patient::class . '" matches no property')));
    }

    /**
     * LQ-N-L1: a typo in a pattern key (config or attribute, entity or property level) stops the run before any write
     * instead of matching no row and leaving the personal data in place.
     */
    public function testUnknownPatternFieldsStopTheRunBeforeAnyWrite(): void
    {
        $entities                                                            = self::ENTITIES;
        $entities[Patient::class]['include_patterns']                        = ['stauts' => 'active'];
        $entities[Patient::class]['properties']['email']['exclude_patterns'] = [['email' => '%@keep.test'], ['contact.zip' => '1%']];

        [$exitCode, $out] = $this->runCommand($entities);

        self::assertNotSame(0, $exitCode, $out);
        $out = (string) preg_replace('/\s+/', ' ', $out);
        self::assertStringContainsString('Unknown field "stauts" in the include patterns of ' . Patient::class, $out);
        self::assertStringContainsString('Unknown field "contact.zip" in the exclude patterns of ' . Patient::class . '::email', $out);
        self::assertSame('ana@example.test', $this->em->getConnection()->fetchOne("SELECT email FROM ddd_patient WHERE id = 'p-1'"));
    }

    public function testKnownColumnsAndEmbeddedColumnsPassThePatternCheck(): void
    {
        $entities                                     = self::ENTITIES;
        $entities[Patient::class]['include_patterns'] = [['status' => 'active'], ['contact_city' => 'Madrid', 'anonymized' => '0']];
        $provider                                     = new ConfigAnonymizeMetadataProvider($entities);
        $service                                      = new AnonymizeService(new FakerFactory('en_US'), new PatternMatcher(), null, null, $provider);

        self::assertSame([], (new PreFlightCheckService(new FakerFactory('en_US'), $provider))->performChecks($this->em, $service->getAnonymizableEntities($this->em)));
    }

    public function testGenerateColumnCommandCoversConfiguredFlagEntities(): void
    {
        $this->em->getConnection()->executeStatement('ALTER TABLE ddd_patient RENAME TO ddd_patient_old');
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'doctrine.orm.default_entity_manager']);
        $doctrine->method('getManager')->willReturn($this->em);

        $command = new GenerateAnonymizedColumnCommand($this->service(), $this->environmentProtection(), $doctrine, []);
        $input   = new ArrayInput([]);
        $input->setInteractive(false);
        $output = new BufferedOutput();

        self::assertSame(0, $command->run($input, $output));
        self::assertStringContainsString('ALTER TABLE "ddd_patient" ADD COLUMN "anonymized"', $output->fetch());
    }

    /**
     * @param array<string, array<string, mixed>> $entities
     */
    private function service(array $entities = self::ENTITIES): AnonymizeService
    {
        return new AnonymizeService(new FakerFactory('en_US'), new PatternMatcher(), null, null, new ConfigAnonymizeMetadataProvider($entities));
    }

    private function environmentProtection(): EnvironmentProtectionService
    {
        return new EnvironmentProtectionService(new ParameterBag(['kernel.environment' => 'test', 'kernel.project_dir' => $this->tempDir]), []);
    }

    /**
     * @param array<string, array<string, mixed>> $entities
     *
     * @return array{int, string}
     */
    private function runCommand(array $entities = self::ENTITIES): array
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'doctrine.orm.default_entity_manager']);
        $doctrine->method('getManager')->willReturn($this->em);

        $command = new AnonymizeCommand(
            $this->service($entities),
            new PreFlightCheckService(new FakerFactory('en_US'), new ConfigAnonymizeMetadataProvider($entities)),
            new AnonymizationHistoryService($this->tempDir . '/history'),
            $this->environmentProtection(),
            $doctrine,
            new ParameterBag(['nowo_anonymize.stats_output_dir' => '%kernel.project_dir%/var/stats']),
            $this->tempDir,
        );

        $input = new ArrayInput(['--force' => true]);
        $input->setInteractive(false);
        $output   = new BufferedOutput();
        $exitCode = $command->run($input, $output);
        $this->em->clear();

        return [$exitCode, $output->fetch()];
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (array_diff((array) scandir($dir), ['.', '..']) as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
