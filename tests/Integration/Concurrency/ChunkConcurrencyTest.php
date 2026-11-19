<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Integration\Concurrency;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Tools\SchemaTool;
use Nowo\AnonymizeBundle\Attribute\Anonymize;
use Nowo\AnonymizeBundle\Event\AnonymizePropertyEvent;
use Nowo\AnonymizeBundle\Faker\FakerFactory;
use Nowo\AnonymizeBundle\Service\AnonymizeService;
use Nowo\AnonymizeBundle\Service\PatternMatcher;
use Nowo\AnonymizeBundle\Tests\Integration\Concurrency\Entity\CcCustomer;
use Nowo\AnonymizeBundle\Tests\Integration\Concurrency\Entity\CcLine;
use Nowo\TransactionContract\TransientStorageFailure;
use Nowo\TransactionDoctrine\TransientFailureClassifier;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Throwable;

use function getenv;
use function is_string;
use function sys_get_temp_dir;

use const PHP_VERSION_ID;

/**
 * REQ-RUNTIME-003: the anonymizer against an application that keeps writing, with two real connections on MySQL 8.4,
 * MariaDB 11.4 and PostgreSQL 16 (CI job "Load readiness"; set ANONYMIZE_TEST_DATABASE_URL, e.g.
 * mysql://root@127.0.0.1:3306/anonymize?serverVersion=8.4.0, postgresql://postgres@127.0.0.1:5432/anonymize?serverVersion=16).
 *
 * Connection A runs the anonymizer; connection B plays the application. Both sessions use a 1 second lock timeout, so
 * a missing lock or a hang fails fast.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
#[Group('database')]
final class ChunkConcurrencyTest extends TestCase
{
    private ?EntityManager $em = null;

    private ?Connection $app = null;

    protected function tearDown(): void
    {
        $this->app?->close();
        $this->em?->getConnection()->close();
        $this->app = null;
        $this->em  = null;
    }

    /**
     * Between the chunk's read and its write the rows are locked: an application write to a row of the chunk being
     * anonymized waits (here: times out) instead of landing between the read and the UPDATE, so the anonymized values
     * are never derived from a row version the application already replaced. A row of a later chunk is not locked:
     * the application's write to it lands, and that chunk then anonymizes the new value. Every row ends anonymized and
     * the application's connection is usable afterwards.
     */
    public function testApplicationWriteToARowOfTheCurrentChunkWaitsForItsCommit(): void
    {
        $em  = $this->entityManager();
        $app = $this->appConnection();
        $this->insertCustomers($em->getConnection(), 4);

        $outcome    = [];
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(AnonymizePropertyEvent::class, function (AnonymizePropertyEvent $event) use ($app, &$outcome): void {
            if ((int) $event->getRecord()['id'] !== 1 || isset($outcome['same chunk'])) {
                return;
            }
            // Row 2 is in this chunk (batch 2): locked until the chunk commits
            $outcome['same chunk'] = $this->tryWrite($app, 'UPDATE cc_customer SET email = ? WHERE id = 2', ['real-2-new@example.test']);
            // Row 3 is in the next chunk: not locked yet
            $outcome['next chunk'] = $this->tryWrite($app, 'UPDATE cc_customer SET email = ? WHERE id = 3', ['real-3-new@example.test']);
            // The joined account row is not part of the lock where the platform can say so (FOR UPDATE OF)
            $outcome['joined row'] = $this->tryWrite($app, 'UPDATE cc_account SET status = ? WHERE id = 1', ['active']);
        });

        $stats = $this->anonymize($em, CcCustomer::class, 2, $dispatcher);

        self::assertSame(4, $stats['updated']);
        self::assertSame('lock conflict', $outcome['same chunk'], 'a write to a row of the chunk being anonymized must wait for its commit');
        self::assertSame('ok', $outcome['next chunk']);
        self::assertSame($em->getConnection()->getDatabasePlatform() instanceof MariaDBPlatform ? 'lock conflict' : 'ok', $outcome['joined row']);

        $emails = $app->fetchFirstColumn('SELECT email FROM cc_customer ORDER BY id');
        foreach ($emails as $i => $email) {
            self::assertStringNotContainsString('real-', (string) $email, 'row ' . ($i + 1) . ' kept real data');
        }

        // Once the run is over the application writes again: the connection was not left broken
        self::assertSame(1, $app->executeStatement('UPDATE cc_customer SET email = ? WHERE id = 2', ['after@example.test']));
    }

    /**
     * A lock conflict on a chunk rolls back that chunk only and is rethrown as TransientStorageFailure; earlier chunks
     * stay committed, nothing of the conflicting chunk is written, the anonymizer's connection is back at nesting level
     * 0 and usable, and a re-run finishes the job.
     */
    public function testLockConflictRollsBackOnlyTheCurrentChunkAndARerunCompletes(): void
    {
        $em         = $this->entityManager();
        $connection = $em->getConnection();
        $app        = $this->appConnection();
        $this->insertCustomers($connection, 4);

        $app->beginTransaction();
        $app->fetchAllAssociative('SELECT id FROM cc_customer WHERE id = 3 FOR UPDATE');

        try {
            $this->anonymize($em, CcCustomer::class, 2);
            self::fail('Expected a TransientStorageFailure');
        } catch (TransientStorageFailure $e) {
            self::assertTrue((new TransientFailureClassifier())->isTransient($e->getPrevious() ?? $e));
        } finally {
            $app->rollBack();
        }

        self::assertSame(0, $connection->getTransactionNestingLevel());
        $emails = $connection->fetchFirstColumn('SELECT email FROM cc_customer ORDER BY id');
        self::assertStringNotContainsString('real-', (string) $emails[0]);
        self::assertStringNotContainsString('real-', (string) $emails[1]);
        self::assertSame(['real-3@example.test', 'real-4@example.test'], [$emails[2], $emails[3]], 'the conflicting chunk wrote nothing');

        $stats = $this->anonymize($em, CcCustomer::class, 2);

        self::assertSame(4, $stats['updated']);
        foreach ($connection->fetchFirstColumn('SELECT email FROM cc_customer') as $email) {
            self::assertStringNotContainsString('real-', (string) $email);
        }
    }

    /**
     * Composite keys use keyset chunks: the application deleting a row of a chunk already done does not shift a later
     * row out of the run (with OFFSET chunks, row (2,1) stayed un-anonymized).
     */
    public function testCompositeKeyRunDoesNotSkipRowsWhenTheApplicationDeletes(): void
    {
        $em         = $this->entityManager();
        $connection = $em->getConnection();
        $app        = $this->appConnection();
        foreach ([[1, 1], [1, 2], [2, 1], [2, 2], [3, 1], [3, 2]] as [$order, $line]) {
            $connection->insert('cc_line', ['order_id' => $order, 'line_no' => $line, 'email' => 'real-' . $order . '-' . $line . '@example.test']);
        }

        $deleted = false;
        $stats   = $this->anonymize($em, CcLine::class, 2, null, static function (int $current) use ($app, &$deleted): void {
            if ($current === 2 && !$deleted) {
                $deleted = true;
                // After the first chunk committed (outside its transaction): the application deletes a row of it
                self::assertSame(1, $app->executeStatement('DELETE FROM cc_line WHERE order_id = 1 AND line_no = 1'));
            }
        });

        self::assertSame(6, $stats['processed']);
        $emails = $connection->fetchFirstColumn('SELECT email FROM cc_line ORDER BY order_id, line_no');
        self::assertCount(5, $emails);
        foreach ($emails as $email) {
            self::assertStringNotContainsString('real-', (string) $email);
        }
    }

    /**
     * @param class-string $class
     *
     * @return array{processed: int, updated: int, propertyStats: array<string, int>}
     */
    private function anonymize(EntityManager $em, string $class, int $batchSize, ?EventDispatcher $dispatcher = null, ?callable $progress = null): array
    {
        $service    = new AnonymizeService(new FakerFactory('en_US'), new PatternMatcher(), $dispatcher);
        $metadata   = $em->getClassMetadata($class);
        $reflection = new ReflectionClass($class);
        // The customers' relationship pattern joins cc_account (LEFT JOIN in the locking read); NULL accounts match "".
        $attribute = $class === CcCustomer::class ? new Anonymize(excludePatterns: ['account.status' => 'never']) : new Anonymize();

        return $service->anonymizeEntity($em, $metadata, $reflection, $service->getAnonymizableProperties($reflection, $metadata), $batchSize, false, null, $progress, $attribute);
    }

    /**
     * @param list<mixed> $params
     */
    private function tryWrite(Connection $connection, string $sql, array $params): string
    {
        try {
            $connection->executeStatement($sql, $params);

            return 'ok';
        } catch (Throwable $e) {
            return (new TransientFailureClassifier())->isTransient($e) ? 'lock conflict' : $e::class . ': ' . $e->getMessage();
        }
    }

    private function insertCustomers(Connection $connection, int $count): void
    {
        $connection->insert('cc_account', ['id' => 1, 'status' => 'active']);
        for ($id = 1; $id <= $count; ++$id) {
            $connection->insert('cc_customer', ['id' => $id, 'email' => 'real-' . $id . '@example.test', 'account_id' => 1]);
        }
    }

    private function appConnection(): Connection
    {
        $app = DriverManager::getConnection($this->params());
        $this->shortLockTimeout($app);
        $this->app = $app;

        return $app;
    }

    private function shortLockTimeout(Connection $connection): void
    {
        if ($connection->getDatabasePlatform() instanceof PostgreSQLPlatform) {
            $connection->executeStatement("SET lock_timeout = '1s'");

            return;
        }
        $connection->executeStatement('SET SESSION innodb_lock_wait_timeout = 1');
    }

    /**
     * @return array<string, mixed>
     */
    private function params(): array
    {
        $url = getenv('ANONYMIZE_TEST_DATABASE_URL');
        if (!is_string($url) || $url === '') {
            self::markTestSkipped('Set ANONYMIZE_TEST_DATABASE_URL (MySQL, MariaDB or PostgreSQL) to run the concurrency tests.');
        }

        return (new DsnParser(['mysql' => 'pdo_mysql', 'mariadb' => 'pdo_mysql', 'pgsql' => 'pdo_pgsql', 'postgresql' => 'pdo_pgsql', 'postgres' => 'pdo_pgsql']))->parse($url);
    }

    private function entityManager(): EntityManager
    {
        $params = $this->params();
        $config = new Configuration();
        $config->setMetadataDriverImpl(new AttributeDriver([__DIR__ . '/Entity']));
        if (PHP_VERSION_ID >= 80400) {
            $config->enableNativeLazyObjects(true);
        } else {
            $config->setProxyDir(sys_get_temp_dir());
            $config->setProxyNamespace('AnonymizeConcurrencyProxies');
        }

        $em = new EntityManager(DriverManager::getConnection($params, $config), $config);
        $this->shortLockTimeout($em->getConnection());
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $tool     = new SchemaTool($em);
        $tool->dropSchema($metadata);
        $tool->createSchema($metadata);
        $this->em = $em;

        return $em;
    }
}
