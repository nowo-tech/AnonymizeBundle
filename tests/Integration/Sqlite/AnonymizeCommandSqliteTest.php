<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Integration\Sqlite;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use Nowo\AnonymizeBundle\Attribute\Anonymize;
use Nowo\AnonymizeBundle\Command\AnonymizeCommand;
use Nowo\AnonymizeBundle\Faker\FakerFactory;
use Nowo\AnonymizeBundle\Service\AnonymizationHistoryService;
use Nowo\AnonymizeBundle\Service\AnonymizeService;
use Nowo\AnonymizeBundle\Service\EnvironmentProtectionService;
use Nowo\AnonymizeBundle\Service\PatternMatcher;
use Nowo\AnonymizeBundle\Service\PreFlightCheckService;
use Nowo\AnonymizeBundle\Tests\Integration\Sqlite\Entity\E2eAuditLog;
use Nowo\AnonymizeBundle\Tests\Integration\Sqlite\Entity\E2eCompany;
use Nowo\AnonymizeBundle\Tests\Integration\Sqlite\Entity\E2eCustomer;
use Nowo\AnonymizeBundle\Tests\Integration\Sqlite\Entity\E2eDocument;
use Nowo\AnonymizeBundle\Tests\Integration\Sqlite\Entity\E2eTempDocument;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\ServiceLocator;

use const PHP_VERSION_ID;

/**
 * End-to-end runs of nowo:anonymize:run against a real Doctrine ORM + SQLite (in memory) schema:
 * relationship patterns, embedded fields, AnonymizableTrait on a parent class, STI truncate,
 * custom entity anonymizer service and the interactive flow.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class AnonymizeCommandSqliteTest extends TestCase
{
    private EntityManager $em;

    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/anonymize_sqlite_e2e_' . uniqid();
        mkdir($this->tempDir, 0o755, true);

        $config = new Configuration();
        $config->setMetadataDriverImpl(new AttributeDriver([__DIR__ . '/Entity']));
        if (PHP_VERSION_ID >= 80400) {
            $config->enableNativeLazyObjects(true);
        } else {
            $config->setProxyDir($this->tempDir . '/proxies');
            $config->setProxyNamespace('AnonymizeSqliteE2eProxies');
            $config->setAutoGenerateProxyClasses(true);
        }

        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $this->em   = new EntityManager($connection, $config);
        (new SchemaTool($this->em))->createSchema($this->em->getMetadataFactory()->getAllMetadata());

        $acme   = new E2eCompany('Acme Corp');
        $globex = new E2eCompany('Globex');
        $this->em->persist($acme);
        $this->em->persist($globex);
        $this->em->persist(new E2eCustomer('alice@example.test', 'Alice', 'Ali', 25, false, $acme, 'secret note'));
        $this->em->persist(new E2eCustomer('bob@keep.test', 'Bob', null, 15, true, $acme));
        $this->em->persist(new E2eCustomer('carol@example.test', 'Carol', 'Caz', 50, false, $globex, 'other note'));
        $this->em->persist(new E2eDocument('Contract'));
        $this->em->persist(new E2eDocument('Invoice'));
        $this->em->persist(new E2eTempDocument('Draft 1'));
        $this->em->persist(new E2eTempDocument('Draft 2'));
        $this->em->persist(new E2eTempDocument('Draft 3'));
        $this->em->persist(new E2eAuditLog('user alice logged in'));
        $this->em->flush();
        $this->em->clear();
    }

    protected function tearDown(): void
    {
        $this->em->close();
        $this->removeDirectory($this->tempDir);
    }

    public function testForcedRunAnonymizesMatchingRowsTruncatesStiChildAndUsesCustomService(): void
    {
        [$exitCode, $out] = $this->runCommand(['--force' => true, '--debug' => true], verbosity: OutputInterface::VERBOSITY_VERBOSE);

        self::assertSame(0, $exitCode, $out);

        $customers = $this->customers();

        // Acme customer: every property anonymized, anonymized flag set by the parent-class trait
        $alice = $customers[1];
        self::assertNotSame('alice@example.test', $alice['email']);
        self::assertStringContainsString('@', (string) $alice['email']);
        self::assertSame('A****', $alice['name']);
        self::assertNotSame('Ali', $alice['nick_name']);
        self::assertGreaterThanOrEqual(30, (int) $alice['customer_age']);
        self::assertLessThanOrEqual(40, (int) $alice['customer_age']);
        self::assertNotSame('secret note', $alice['notes']);
        self::assertSame('Anonymized street 1', $alice['address_street']);
        self::assertSame(1, (int) $alice['anonymized']);

        // Acme customer with excluded e-mail, NULL columns and a pattern-gated property
        $bob = $customers[2];
        self::assertSame('bob@keep.test', $bob['email']);
        self::assertNull($bob['nick_name']);
        self::assertNull($bob['notes']);
        self::assertSame(1, (int) $bob['vip'], 'vip is only anonymized when customer_age >= 18');

        // Globex customer: excluded by the entity-level relationship pattern company.name
        $carol = $customers[3];
        self::assertSame('carol@example.test', $carol['email']);
        self::assertSame('Carol', $carol['name']);
        self::assertSame(0, (int) $carol['anonymized']);

        // STI truncate only removed the "temp" rows
        $kinds = $this->em->getConnection()->fetchFirstColumn('SELECT kind FROM e2e_document ORDER BY id');
        self::assertSame(['document', 'document'], $kinds);

        // Custom anonymizer service
        self::assertSame('redacted #1', $this->em->getConnection()->fetchOne('SELECT message FROM e2e_audit_log'));

        self::assertStringContainsString('emptied', $out);
        self::assertStringContainsString('Property statistics:', $out);
        self::assertStringContainsString('Include patterns: {"customer_age":">=18"}', $out);
        self::assertStringContainsString('Exclude patterns: {"email":"%@keep.test"}', $out);
        self::assertStringContainsString('Options: {"preserve_start":1}', $out);
        self::assertStringContainsString('Anonymization: custom service (no property attributes)', $out);
    }

    public function testDryRunWithCustomLocaleCountsWithoutWriting(): void
    {
        [$exitCode, $out] = $this->runCommand(['--dry-run' => true, '--locale' => 'es_ES', '--no-progress' => true]);

        self::assertSame(0, $exitCode, $out);
        self::assertStringContainsString('e2e_document: 3 record(s) would be deleted', $out);
        self::assertMatchesRegularExpression('/Processed: 3 records, Updated: 2 records/', $out);
        // The custom anonymizer service is still resolved with a non-default locale
        self::assertMatchesRegularExpression('/Processed: 1 records, Updated: 1 records/', $out);

        self::assertSame('alice@example.test', $this->customers()[1]['email']);
        self::assertSame(5, (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM e2e_document'));
        self::assertSame('user alice logged in', $this->em->getConnection()->fetchOne('SELECT message FROM e2e_audit_log'));
    }

    public function testInteractiveRunConfirmsManagerAndSkipsDeclinedEntities(): void
    {
        [$exitCode, $out] = $this->runCommand(
            ['--interactive' => true, '--dry-run' => true, '--entity' => [E2eCustomer::class, E2eAuditLog::class]],
            "yes\nyes\nno\nno\n",
            OutputInterface::VERBOSITY_VERBOSE,
        );

        self::assertSame(0, $exitCode, $out);
        self::assertStringContainsString('Entities filter:', $out);
        self::assertStringContainsString('DRY RUN MODE: No changes will be made', $out);
        self::assertStringContainsString('entity(ies) to process in', $out);
        self::assertStringContainsString('custom anonymize service', $out);
        self::assertStringContainsString('Properties to anonymize:', $out);
        self::assertSame(2, substr_count($out, 'Skipping entity:'));
    }

    public function testInteractiveRunConfirmsEntityAndProcessesIt(): void
    {
        [$exitCode, $out] = $this->runCommand(
            ['--interactive' => true, '--dry-run' => true, '--entity' => [E2eAuditLog::class]],
            "yes\nyes\nyes\n",
        );

        self::assertSame(0, $exitCode, $out);
        self::assertStringNotContainsString('Skipping entity:', $out);
        self::assertMatchesRegularExpression('/Processed: 1 records, Updated: 1 records/', $out);
    }

    public function testInteractiveRunCanSkipTheWholeManager(): void
    {
        [$exitCode, $out] = $this->runCommand(['--interactive' => true, '--dry-run' => true], "yes\nno\n");

        self::assertSame(0, $exitCode, $out);
        self::assertStringContainsString('Skipping entity manager: default', $out);
        self::assertStringNotContainsString('Processing entity:', $out);
    }

    public function testInteractiveTruncateDeclinedAbortsWithoutChanges(): void
    {
        [$exitCode, $out] = $this->runCommand(['--interactive' => true], "yes\nyes\nno\n");

        self::assertSame(1, $exitCode, $out);
        self::assertStringContainsString('Truncate aborted.', $out);
        self::assertSame(5, (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM e2e_document'));
    }

    /**
     * SEC-004 M1: --stats-only only reduces the output; without --force a non-interactive run must not write.
     */
    public function testNonInteractiveStatsOnlyWithoutForceRefusesAndWritesNothing(): void
    {
        [$exitCode, $out] = $this->runCommand(['--stats-only' => true]);

        self::assertSame(1, $exitCode, $out);
        self::assertStringContainsString('Refusing destructive anonymization without --force', $out);
        self::assertSame('alice@example.test', $this->customers()[1]['email']);
        self::assertSame(5, (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM e2e_document'));
        self::assertSame('user alice logged in', $this->em->getConnection()->fetchOne('SELECT message FROM e2e_audit_log'));
    }

    /**
     * SEC-004 M1: the TRUNCATE prompt is still asked with --stats-only, and declining it aborts before any write.
     */
    public function testStatsOnlyStillAsksBeforeTruncating(): void
    {
        [$exitCode, $out] = $this->runCommand(['--stats-only' => true], "yes\nno\n");

        self::assertSame(1, $exitCode, $out);
        self::assertStringContainsString('TRUNCATE will permanently empty these tables. Continue?', $out);
        self::assertStringContainsString('Truncate aborted.', $out);
        self::assertSame(5, (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM e2e_document'));
        self::assertSame('alice@example.test', $this->customers()[1]['email']);
    }

    /**
     * SEC-004 I9: a TTY run without --force shows the target databases and does nothing when declined.
     */
    public function testTtyRunWithoutForceListsTargetsAndCanBeDeclined(): void
    {
        [$exitCode, $out] = $this->runCommand([], "no\n");

        self::assertSame(0, $exitCode, $out);
        self::assertStringContainsString('This run overwrites data in place', $out);
        self::assertStringContainsString('default: pdo_sqlite', $out);
        self::assertStringContainsString('Anonymization cancelled by user.', $out);
        self::assertSame('alice@example.test', $this->customers()[1]['email']);
    }

    public function testRelationshipPatternsJoinOnlyOwningToOneAssociationsAndSkipEmbeddedHosts(): void
    {
        $service    = $this->createAnonymizeService();
        $metadata   = $this->em->getClassMetadata(E2eCustomer::class);
        $reflection = new ReflectionClass(E2eCustomer::class);

        $properties = $service->getAnonymizableProperties($reflection);
        // A property list pointing at the embedded host itself (not at address.street) is ignored
        $properties[] = [
            'property'  => $reflection->getProperty('address'),
            'attribute' => $properties[0]['attribute'],
            'weight'    => 99,
            'fieldName' => 'address',
        ];

        $stats = $service->anonymizeEntity(
            $this->em,
            $metadata,
            $reflection,
            $properties,
            1,
            true,
            null,
            null,
            new Anonymize(
                includePatterns: ['company.name' => 'Acme%', 'company.country' => 'ES'],
                // Unknown associations / fields are not joined, so these exclusions never match
                excludePatterns: ['missing.name' => 'x', 'company.unknownField' => 'x'],
            ),
        );

        self::assertSame(3, $stats['processed']);
        self::assertSame(2, $stats['updated']);
        self::assertArrayNotHasKey('address', $stats['propertyStats']);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array{int, string}
     */
    private function runCommand(array $options, ?string $answers = null, int $verbosity = OutputInterface::VERBOSITY_NORMAL): array
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'doctrine.orm.default_entity_manager']);
        $doctrine->method('getManager')->willReturn($this->em);

        $anonymizeService = $this->createAnonymizeService();
        $command          = new AnonymizeCommand(
            $anonymizeService,
            new PreFlightCheckService(new FakerFactory('en_US')),
            new AnonymizationHistoryService($this->tempDir . '/history'),
            new EnvironmentProtectionService(new ParameterBag(['kernel.environment' => 'test', 'kernel.project_dir' => $this->tempDir]), []),
            $doctrine,
            new ParameterBag(['nowo_anonymize.stats_output_dir' => '%kernel.project_dir%/var/stats']),
            $this->tempDir,
        );

        $input = new ArrayInput($options);
        if ($answers !== null) {
            $stream = fopen('php://memory', 'r+');
            self::assertIsResource($stream);
            fwrite($stream, $answers);
            rewind($stream);
            $input->setStream($stream);
        } else {
            $input->setInteractive(false);
        }
        $output = new BufferedOutput($verbosity);

        $exitCode = $command->run($input, $output);
        $this->em->clear();

        return [$exitCode, $output->fetch()];
    }

    private function createAnonymizeService(): AnonymizeService
    {
        $locator = new ServiceLocator(['e2e.audit_log_anonymizer' => static fn (): E2eAuditLogAnonymizer => new E2eAuditLogAnonymizer()]);

        return new AnonymizeService(new FakerFactory('en_US', $locator), new PatternMatcher(), null, $locator);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function customers(): array
    {
        $rows = $this->em->getConnection()->fetchAllAssociative('SELECT * FROM e2e_customer ORDER BY id');

        return array_column($rows, null, 'id');
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
