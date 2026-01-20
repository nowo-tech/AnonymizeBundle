<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Nowo\AnonymizeBundle\Enum\SymfonyService;
use Nowo\AnonymizeBundle\Service\AnonymizeService;
use Nowo\AnonymizeBundle\Service\EnvironmentProtectionService;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command to generate migrations for adding the `anonymized` column to anonymizable entities.
 *
 * This command generates SQL ALTER TABLE statements to add the `anonymized` boolean column
 * to all tables that have entities marked with the #[Anonymize] attribute and use the
 * AnonymizableTrait.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[AsCommand(
    name: 'nowo:anonymize:generate-column-migration',
    description: 'Generate migration SQL to add anonymized column to anonymizable entities'
)]
final class GenerateAnonymizedColumnCommand extends Command
{
    /**
     * Creates a new GenerateAnonymizedColumnCommand instance.
     *
     * @param ContainerInterface $container The service container
     * @param AnonymizeService $anonymizeService The anonymize service
     * @param array<string> $connections The Doctrine connection names to process
     */
    public function __construct(
        private ContainerInterface $container,
        private AnonymizeService $anonymizeService,
        private array $connections = []
    ) {
        parent::__construct();
    }

    /**
     * Configures the command.
     */
    protected function configure(): void
    {
        $this
            ->setHelp(<<<'HELP'
The <info>%command.name%</info> command generates SQL ALTER TABLE statements to add
the <comment>anonymized</comment> boolean column to all tables that have entities marked
with the <comment>#[Anonymize]</comment> attribute.

This command checks if entities use the <comment>AnonymizableTrait</comment> and generates
migrations only for those entities.

<info>php %command.full_name%</info>

To generate migrations for a specific connection:

<info>php %command.full_name% --connection=default</info>

To save the output to a file:

<info>php %command.full_name% --output=migration.sql</info>
HELP
            )
            ->addOption(
                'connection',
                'c',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'The Doctrine connection to use'
            )
            ->addOption(
                'output',
                'o',
                InputOption::VALUE_OPTIONAL,
                'Output file path for the migration SQL (default: prints to console)'
            );
    }

    /**
     * Executes the command.
     *
     * @param InputInterface $input The input interface
     * @param OutputInterface $output The output interface
     * @return int The command exit code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Generate Anonymized Column Migration');

        $connections = $input->getOption('connection');
        if (empty($connections)) {
            $connections = $this->connections;
            if (empty($connections)) {
                $connections = ['default'];
            }
        }

        $outputFile = $input->getOption('output');
        $sqlStatements = [];

        foreach ($connections as $connectionName) {
            $io->section(sprintf('Processing connection: <comment>%s</comment>', $connectionName));

            try {
                $em = $this->getEntityManager($connectionName);
                if ($em === null) {
                    $io->warning(sprintf('Connection "%s" not found, skipping.', $connectionName));
                    continue;
                }

                $entities = $this->anonymizeService->getAnonymizableEntities($em);
                $connection = $em->getConnection();

                foreach ($entities as $className => $entityData) {
                    $metadata = $entityData['metadata'];
                    $reflection = $entityData['reflection'];

                    // Check if entity uses AnonymizableTrait
                    if (!$this->usesAnonymizableTrait($reflection)) {
                        continue;
                    }

                    $tableName = $metadata->getTableName();
                    $schemaManager = $connection->createSchemaManager();

                    // Check if column already exists
                    if ($schemaManager->tablesExist([$tableName])) {
                        $columns = $schemaManager->listTableColumns($tableName);
                        $columnExists = false;
                        foreach ($columns as $column) {
                            if ($column->getName() === 'anonymized') {
                                $columnExists = true;
                                break;
                            }
                        }

                        if ($columnExists) {
                            $io->text(sprintf('  ✓ Column <comment>anonymized</comment> already exists in <info>%s</info>', $tableName));
                            continue;
                        }
                    }

                    // Generate ALTER TABLE statement
                    $sql = sprintf(
                        'ALTER TABLE %s ADD COLUMN %s BOOLEAN NOT NULL DEFAULT FALSE;',
                        $connection->quoteSingleIdentifier($tableName),
                        $connection->quoteSingleIdentifier('anonymized')
                    );

                    $sqlStatements[] = [
                        'connection' => $connectionName,
                        'table' => $tableName,
                        'entity' => $className,
                        'sql' => $sql,
                    ];

                    $io->text(sprintf('  + Generated migration for <info>%s</info> (<comment>%s</comment>)', $tableName, $className));
                }
            } catch (\Exception $e) {
                $io->error(sprintf('Error processing connection "%s": %s', $connectionName, $e->getMessage()));
                continue;
            }
        }

        if (empty($sqlStatements)) {
            $io->success('No migrations needed. All tables already have the anonymized column or no entities use AnonymizableTrait.');
            return Command::SUCCESS;
        }

        // Generate SQL output
        $sqlOutput = "-- Anonymized Column Migration\n";
        $sqlOutput .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";

        foreach ($sqlStatements as $statement) {
            $sqlOutput .= sprintf("-- Connection: %s, Table: %s, Entity: %s\n", $statement['connection'], $statement['table'], $statement['entity']);
            $sqlOutput .= $statement['sql'] . "\n\n";
        }

        if ($outputFile !== null) {
            file_put_contents($outputFile, $sqlOutput);
            $io->success(sprintf('Migration SQL saved to: %s', $outputFile));
        } else {
            $io->section('Generated Migration SQL:');
            $io->text($sqlOutput);
            $io->note('Use --output option to save to a file');
        }

        return Command::SUCCESS;
    }

    /**
     * Gets the entity manager for the given connection name.
     *
     * @param string $connectionName The connection name
     * @return EntityManagerInterface|null The entity manager or null if not found
     */
    private function getEntityManager(string $connectionName): ?EntityManagerInterface
    {
        try {
            $doctrine = $this->container->get(SymfonyService::DOCTRINE);
            $allManagers = $doctrine->getManagerNames();

            // Try to get manager by name
            if (isset($allManagers[$connectionName])) {
                return $doctrine->getManager($connectionName);
            }

            // If connection name is 'default' or empty, try default manager
            if ($connectionName === 'default' || empty($connectionName)) {
                return $doctrine->getManager();
            }

            // Try to find manager by connection name
            foreach ($allManagers as $managerName => $serviceId) {
                $manager = $doctrine->getManager($managerName);
                if ($manager->getConnection()->getName() === $connectionName) {
                    return $manager;
                }
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Checks if a class uses the AnonymizableTrait.
     *
     * @param \ReflectionClass $reflection The reflection class
     * @return bool True if the class uses AnonymizableTrait
     */
    private function usesAnonymizableTrait(\ReflectionClass $reflection): bool
    {
        $traitName = 'Nowo\\AnonymizeBundle\\Trait\\AnonymizableTrait';

        foreach ($reflection->getTraitNames() as $trait) {
            if ($trait === $traitName) {
                return true;
            }
        }

        // Check parent classes
        $parent = $reflection->getParentClass();
        while ($parent !== false) {
            foreach ($parent->getTraitNames() as $trait) {
                if ($trait === $traitName) {
                    return true;
                }
            }
            $parent = $parent->getParentClass();
        }

        return false;
    }
}
