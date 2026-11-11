<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Integration\PatternFields;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Tools\SchemaTool;
use Nowo\AnonymizeBundle\Attribute\Anonymize;
use Nowo\AnonymizeBundle\Faker\FakerFactory;
use Nowo\AnonymizeBundle\Service\AnonymizeService;
use Nowo\AnonymizeBundle\Service\PatternMatcher;
use Nowo\AnonymizeBundle\Service\PreFlightCheckService;
use Nowo\AnonymizeBundle\Tests\Integration\PatternFields\Entity\PfCustomer;
use Nowo\AnonymizeBundle\Tests\Integration\PatternFields\Entity\PfParty;
use Nowo\AnonymizeBundle\Tests\Integration\PatternFields\Entity\PfPerson;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

use function array_filter;
use function array_values;
use function getenv;
use function implode;
use function is_string;
use function str_starts_with;

use const PHP_VERSION_ID;

/**
 * Pattern keys against real rows: pre-flight and the run must agree on which keys match (audit LQ-N-D-L1, LQ-N-D-I1).
 *
 * Runs on SQLite (in memory) always, and on PostgreSQL when ANONYMIZE_TEST_POSTGRES_URL is set (CI job "PostgreSQL",
 * e.g. pgsql://postgres@127.0.0.1:5432/anonymize?serverVersion=17, passwordless). PostgreSQL folds the unquoted
 * camelCase columns "accountStatus" and "fullName" to "accountstatus" and "fullname".
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
#[Group('postgres')]
final class PatternFieldsTest extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function databases(): iterable
    {
        yield 'sqlite' => ['sqlite'];
        yield 'postgresql' => ['postgresql'];
    }

    /**
     * Both spellings of a camelCase column pass pre-flight and anonymise the row: the metadata spelling and the
     * case-folded one the server returns. A typo is still refused.
     */
    #[DataProvider('databases')]
    public function testCamelCaseColumnKeysAgreeBetweenPreFlightAndRun(string $database): void
    {
        $em         = $this->entityManager($database);
        $metadata   = $em->getClassMetadata(PfCustomer::class);
        $preFlight  = new PreFlightCheckService(new FakerFactory('en_US'));
        $service    = new AnonymizeService(new FakerFactory('en_US'), new PatternMatcher());
        $reflection = new ReflectionClass(PfCustomer::class);

        foreach (['accountStatus', 'accountstatus', 'ACCOUNTSTATUS'] as $key) {
            $em->getConnection()->executeStatement('DELETE FROM pf_customer');
            $this->insertCustomers($em);

            $attribute = new Anonymize(includePatterns: [$key => 'active']);
            self::assertSame([], $this->unknownFieldErrors($preFlight->performChecks($em, [PfCustomer::class => ['metadata' => $metadata, 'reflection' => $reflection, 'attribute' => $attribute]])), $key);

            $stats = $service->anonymizeEntity($em, $metadata, $reflection, $service->getAnonymizableProperties($reflection, $metadata), 100, false, null, null, $attribute);

            self::assertSame(1, $stats['updated'], $key);
            $emails = $em->getConnection()->fetchFirstColumn('SELECT email FROM pf_customer ORDER BY id');
            self::assertNotSame('ana@example.test', $emails[0], $key);
            self::assertSame('bob@example.test', $emails[1], $key);
        }

        $typo   = new Anonymize(includePatterns: ['acountStatus' => 'active']);
        $errors = $this->unknownFieldErrors($preFlight->performChecks($em, [PfCustomer::class => ['metadata' => $metadata, 'reflection' => $reflection, 'attribute' => $typo]]));
        self::assertCount(1, $errors);
        self::assertStringContainsString('Unknown field "acountStatus"', $errors[0]);
    }

    /**
     * LQ-N-D2-L1: an anonymised camelCase column passes pre-flight (one column differing only in letter case) and is
     * anonymised, through the row's own key, on PostgreSQL too. A row is never counted as updated while it keeps it.
     */
    #[DataProvider('databases')]
    public function testCamelCaseAnonymizedColumnIsAnonymized(string $database): void
    {
        $em         = $this->entityManager($database);
        $metadata   = $em->getClassMetadata(PfCustomer::class);
        $reflection = new ReflectionClass(PfCustomer::class);
        $service    = new AnonymizeService(new FakerFactory('en_US'), new PatternMatcher());
        $this->insertCustomers($em);

        $errors = (new PreFlightCheckService(new FakerFactory('en_US')))->performChecks($em, [PfCustomer::class => ['metadata' => $metadata, 'reflection' => $reflection, 'attribute' => new Anonymize()]]);
        self::assertSame([], $errors, implode("\n", $errors));

        $stats = $service->anonymizeEntity($em, $metadata, $reflection, $service->getAnonymizableProperties($reflection, $metadata), 100, false, null, null, new Anonymize());

        self::assertSame(2, $stats['updated']);
        self::assertSame(['email' => 2, 'fullName' => 2], $stats['propertyStats']);
        $rows = $em->getConnection()->fetchAllNumeric('SELECT email, ' . $metadata->getColumnName('fullName') . ' FROM pf_customer ORDER BY id');
        self::assertNotSame('ana@example.test', $rows[0][0]);
        self::assertNotSame('Ana Real', $rows[0][1]);
        self::assertNotSame('Bob Real', $rows[1][1]);
    }

    /**
     * LQ-N-D2-L1: a mapped anonymised column missing from the table (no pre-flight, direct library call) stops the
     * chunk with an exception; nothing is written and nothing is counted as updated.
     */
    public function testMissingAnonymizedColumnFailsInsteadOfKeepingTheValue(): void
    {
        $em         = $this->entityManager('sqlite');
        $metadata   = $em->getClassMetadata(PfCustomer::class);
        $reflection = new ReflectionClass(PfCustomer::class);
        $service    = new AnonymizeService(new FakerFactory('en_US'), new PatternMatcher());
        $connection = $em->getConnection();
        $connection->executeStatement('DROP TABLE pf_customer');
        $connection->executeStatement('CREATE TABLE pf_customer (id INTEGER NOT NULL PRIMARY KEY, email VARCHAR(180) NOT NULL, accountStatus VARCHAR(20) NOT NULL)');
        $connection->insert('pf_customer', ['id' => 1, 'email' => 'ana@example.test', 'accountStatus' => 'active']);

        try {
            $service->anonymizeEntity($em, $metadata, $reflection, $service->getAnonymizableProperties($reflection, $metadata), 100, false, null, null, new Anonymize());
            self::fail('Expected a RuntimeException');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('Cannot anonymize ' . PfCustomer::class . '::fullName: column "fullName" is not in the rows read from table "pf_customer"', $e->getMessage());
        }

        self::assertSame('ana@example.test', $connection->fetchOne('SELECT email FROM pf_customer WHERE id = 1'));
        self::assertFalse($connection->isTransactionActive());
    }

    /**
     * A JOINED child is read from its own table: inherited columns, the root discriminator and inherited associations
     * are not in the row, so pre-flight refuses them there. The root keeps them.
     */
    #[DataProvider('databases')]
    public function testJoinedChildAcceptsOnlyItsOwnTableColumns(string $database): void
    {
        $em        = $this->entityManager($database);
        $preFlight = new PreFlightCheckService(new FakerFactory('en_US'));

        $own = ['id' => '1', 'nickname' => 'x', 'NickName' => 'x', 'employer_id' => '1', 'employer.email' => 'x%'];
        self::assertSame([], $this->unknownFieldErrors($this->check($em, $preFlight, PfPerson::class, $own)));

        $inherited = ['display_name' => 'x', 'kind' => 'person', 'owner_id' => '1', 'owner.email' => 'x%'];
        $errors    = $this->unknownFieldErrors($this->check($em, $preFlight, PfPerson::class, $inherited));
        self::assertCount(4, $errors, implode("\n", $errors));
        self::assertStringContainsString('"display_name" in the include patterns of ' . PfPerson::class, $errors[0]);
        self::assertStringContainsString('table "pf_person"', $errors[0]);
        self::assertStringContainsString('"kind"', $errors[1]);
        self::assertStringContainsString('"owner_id"', $errors[2]);
        self::assertStringContainsString('"owner.email"', $errors[3]);

        self::assertSame([], $this->unknownFieldErrors($this->check($em, $preFlight, PfParty::class, $inherited)));
    }

    private function insertCustomers(EntityManager $em): void
    {
        $metadata = $em->getClassMetadata(PfCustomer::class);
        $em->getConnection()->insert('pf_customer', ['id' => 1, 'email' => 'ana@example.test', $metadata->getColumnName('accountStatus') => 'active', $metadata->getColumnName('fullName') => 'Ana Real']);
        $em->getConnection()->insert('pf_customer', ['id' => 2, 'email' => 'bob@example.test', $metadata->getColumnName('accountStatus') => 'closed', $metadata->getColumnName('fullName') => 'Bob Real']);
    }

    /**
     * @param class-string $class
     * @param array<string, string> $patterns
     *
     * @return array<string>
     */
    private function check(EntityManager $em, PreFlightCheckService $preFlight, string $class, array $patterns): array
    {
        return $preFlight->performChecks($em, [$class => [
            'metadata'   => $em->getClassMetadata($class),
            'reflection' => new ReflectionClass($class),
            'attribute'  => new Anonymize(includePatterns: $patterns),
        ]]);
    }

    /**
     * @param array<string> $errors
     *
     * @return list<string>
     */
    private function unknownFieldErrors(array $errors): array
    {
        return array_values(array_filter($errors, static fn (string $e): bool => str_starts_with($e, 'Unknown field')));
    }

    private function entityManager(string $database): EntityManager
    {
        $config = new Configuration();
        $config->setMetadataDriverImpl(new AttributeDriver([__DIR__ . '/Entity']));
        if (PHP_VERSION_ID >= 80400) {
            $config->enableNativeLazyObjects(true);
        } else {
            $config->setProxyDir(sys_get_temp_dir());
            $config->setProxyNamespace('AnonymizePatternFieldsProxies');
        }

        if ($database === 'sqlite') {
            $params = ['driver' => 'pdo_sqlite', 'memory' => true];
        } else {
            $url = getenv('ANONYMIZE_TEST_POSTGRES_URL');
            if (!is_string($url) || $url === '') {
                self::markTestSkipped('Set ANONYMIZE_TEST_POSTGRES_URL to run against PostgreSQL.');
            }
            $params = (new DsnParser(['pgsql' => 'pdo_pgsql']))->parse($url);
        }

        $em       = new EntityManager(DriverManager::getConnection($params, $config), $config);
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $tool     = new SchemaTool($em);
        $tool->dropSchema($metadata);
        $tool->createSchema($metadata);

        return $em;
    }
}
