<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Command;

use Exception;
use Nowo\AnonymizeBundle\Enum\SymfonyService;
use Nowo\AnonymizeBundle\Faker\FakerFactory;
use Nowo\AnonymizeBundle\Service\AnonymizeService;
use Nowo\AnonymizeBundle\Service\PatternMatcher;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function count;
use function sprintf;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const PHP_INT_MAX;

/**
 * Command to display information about anonymizers defined in the application.
 *
 * This command shows:
 * - Location of each anonymizer (entity and property)
 * - Configuration (faker type, options, patterns)
 * - Execution order (based on weight)
 * - Statistics about how many records will be anonymized
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[AsCommand(
    name: 'nowo:anonymize:info',
    description: 'Display information about anonymizers defined in the application',
)]
final class AnonymizeInfoCommand extends AbstractCommand
{
    /**
     * Creates a new AnonymizeInfoCommand instance.
     *
     * @param ContainerInterface $container The service container
     * @param string $locale The default locale for Faker generator (default: 'en_US')
     * @param array<string> $connections The default connections to process (empty = all)
     */
    public function __construct(
        private ContainerInterface $container,
        private string $locale = 'en_US',
        private array $connections = []
    ) {
        parent::__construct();
    }

    /**
     * Configures the command options.
     */
    protected function configure(): void
    {
        $this
            ->setHelp(
                <<<'HELP'
                    The <info>%command.name%</info> command displays detailed information about anonymizers.

                      <info>php %command.full_name%</info>

                    This command will:
                      1. Scan all Doctrine connections for entities with the #[Anonymize] attribute
                      2. List all properties marked with #[AnonymizeProperty] attribute
                      3. Show configuration (faker type, options, patterns)
                      4. Display execution order (based on weight)
                      5. Show statistics about how many records will be anonymized

                    Options:
                      --connection, -c    Process only specific connections (can be used multiple times)
                      --locale, -l       Locale for Faker generator (default: en_US)

                    Examples:
                      <info>php %command.full_name%</info>
                      <info>php %command.full_name% --connection default</info>
                      <info>php %command.full_name% --locale es_ES</info>
                    HELP
            )
            ->addOption('connection', 'c', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Specific connections to process (default: all)')
            ->addOption('locale', 'l', InputOption::VALUE_OPTIONAL, 'Locale for Faker generator', $this->locale);
    }

    /**
     * Executes the command.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io          = new SymfonyStyle($input, $output);
        $locale      = $input->getOption('locale') ?? $this->locale;
        $connections = $input->getOption('connection');

        $io->title('Anonymizer Information');

        // Get Doctrine registry
        $doctrine = $this->container->get(SymfonyService::DOCTRINE);

        // Get all entity manager names
        $allManagers       = $doctrine->getManagerNames();
        $managersToProcess = empty($connections) ? array_keys($allManagers) : array_intersect(array_keys($allManagers), $connections);

        if (empty($managersToProcess)) {
            $io->error('No entity managers found to process.');

            return self::FAILURE;
        }

        // Initialize services
        $fakerFactory     = new FakerFactory($locale, $this->container);
        $patternMatcher   = new PatternMatcher();
        $anonymizeService = new AnonymizeService($fakerFactory, $patternMatcher, null, $this->container);

        $allAnonymizers = [];

        // Process each entity manager
        foreach ($managersToProcess as $managerName) {
            $io->section(sprintf('Entity Manager: <info>%s</info>', $managerName));

            try {
                $em             = $doctrine->getManager($managerName);
                $connection     = $em->getConnection();
                $connectionName = $connection->getDatabase();

                // Get all anonymizable entities
                $entities = $anonymizeService->getAnonymizableEntities($em);

                if (empty($entities)) {
                    $io->note('No entities found with #[Anonymize] attribute');
                    continue;
                }

                $io->writeln(sprintf('Found <info>%d</info> entity(ies) with anonymizers', count($entities)));

                // Process each entity
                foreach ($entities as $className => $entityData) {
                    $metadata        = $entityData['metadata'];
                    $reflection      = $entityData['reflection'];
                    $entityAttribute = $entityData['attribute'];
                    $tableName       = $metadata->getTableName();

                    // Get anonymizable properties (may be empty when entity uses anonymizeService)
                    $properties           = $anonymizeService->getAnonymizableProperties($reflection);
                    $usesAnonymizeService = $entityAttribute->anonymizeService !== null && $entityAttribute->anonymizeService !== '';

                    if (empty($properties) && !$usesAnonymizeService) {
                        continue;
                    }

                    // Get total records count
                    $countQuery   = sprintf('SELECT COUNT(*) as total FROM %s', $this->quoteIdentifier($connection, $tableName));
                    $totalRecords = (int) $connection->fetchOne($countQuery);

                    // Get all records for pattern matching (only needed when we have properties to show samples)
                    $query      = sprintf('SELECT * FROM %s', $this->quoteIdentifier($connection, $tableName));
                    $allRecords = $connection->fetchAllAssociative($query);

                    $io->writeln('');
                    $io->writeln(sprintf('  <comment>Entity:</comment> <info>%s</info>', $className));
                    $io->writeln(sprintf('  <comment>Table:</comment> %s', $tableName));
                    $io->writeln(sprintf('  <comment>Total Records:</comment> %d', $totalRecords));

                    if ($usesAnonymizeService) {
                        $io->writeln(sprintf('  <comment>Anonymization:</comment> custom service <info>%s</info> (no #[AnonymizeProperty] required)', $entityAttribute->anonymizeService));
                    }

                    // Show entity-level patterns if any
                    if (!empty($entityAttribute->includePatterns) || !empty($entityAttribute->excludePatterns)) {
                        $io->writeln('  <comment>Entity Patterns:</comment>');
                        if (!empty($entityAttribute->includePatterns)) {
                            $io->writeln(sprintf('    Include: %s', json_encode($entityAttribute->includePatterns, JSON_PRETTY_PRINT)));
                        }
                        if (!empty($entityAttribute->excludePatterns)) {
                            $io->writeln(sprintf('    Exclude: %s', json_encode($entityAttribute->excludePatterns, JSON_PRETTY_PRINT)));
                        }
                    }

                    if (empty($properties)) {
                        $io->writeln('');
                        continue;
                    }

                    // Sort properties by weight
                    usort($properties, static function ($a, $b) {
                        $weightA = $a['weight'] ?? PHP_INT_MAX;
                        $weightB = $b['weight'] ?? PHP_INT_MAX;
                        if ($weightA === $weightB) {
                            return strcmp($a['property']->getName(), $b['property']->getName());
                        }

                        return $weightA <=> $weightB;
                    });

                    $io->writeln('');
                    $io->writeln(sprintf('  <comment>Properties to Anonymize:</comment> <info>%d</info>', count($properties)));

                    // Display each property
                    foreach ($properties as $index => $propertyData) {
                        $property     = $propertyData['property'];
                        $attribute    = $propertyData['attribute'];
                        $weight       = $propertyData['weight'] ?? PHP_INT_MAX;
                        $propertyName = $property->getName();

                        // Get column name
                        $columnName = $propertyName;
                        if ($metadata->hasField($propertyName)) {
                            $fieldMapping = $metadata->getFieldMapping($propertyName);
                            $columnName   = $fieldMapping['columnName'] ?? $propertyName;
                        }

                        // Count records that will be anonymized for this property
                        $recordsToAnonymize = 0;
                        foreach ($allRecords as $record) {
                            // Check entity-level patterns first
                            $entityMatches = $patternMatcher->matches(
                                $record,
                                $entityAttribute->includePatterns ?? [],
                                $entityAttribute->excludePatterns ?? [],
                            );

                            if (!$entityMatches) {
                                continue;
                            }

                            // Check property-level patterns
                            if ($patternMatcher->matches(
                                $record,
                                $attribute->includePatterns ?? [],
                                $attribute->excludePatterns ?? [],
                            )) {
                                ++$recordsToAnonymize;
                            }
                        }

                        $percentage = $totalRecords > 0 ? round(($recordsToAnonymize / $totalRecords) * 100, 2) : 0;

                        $io->writeln('');
                        $io->writeln(sprintf('    <info>%d.</info> <comment>%s</comment> (Weight: <info>%d</info>)', $index + 1, $propertyName, $weight));
                        $io->writeln(sprintf('         Column: <comment>%s</comment>', $columnName));
                        $io->writeln(sprintf('         Faker Type: <info>%s</info>', $attribute->type));

                        if ($attribute->service) {
                            $io->writeln(sprintf('         Service: <info>%s</info>', $attribute->service));
                        }

                        if (!empty($attribute->options)) {
                            $io->writeln(sprintf('         Options: <comment>%s</comment>', json_encode($attribute->options, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)));
                        }

                        if (!empty($attribute->includePatterns)) {
                            $io->writeln(sprintf('         Include Patterns: <comment>%s</comment>', json_encode($attribute->includePatterns, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)));
                        }

                        if (!empty($attribute->excludePatterns)) {
                            $io->writeln(sprintf('         Exclude Patterns: <comment>%s</comment>', json_encode($attribute->excludePatterns, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)));
                        }

                        $io->writeln(sprintf('         Records to Anonymize: <info>%d</info> / %d (<info>%s%%</info>)', $recordsToAnonymize, $totalRecords, $percentage));

                        // Store for summary
                        $allAnonymizers[] = [
                            'connection'           => $connectionName,
                            'entity'               => $className,
                            'table'                => $tableName,
                            'property'             => $propertyName,
                            'column'               => $columnName,
                            'faker_type'           => $attribute->type,
                            'service'              => $attribute->service,
                            'weight'               => $weight,
                            'options'              => $attribute->options ?? [],
                            'include_patterns'     => $attribute->includePatterns ?? [],
                            'exclude_patterns'     => $attribute->excludePatterns ?? [],
                            'total_records'        => $totalRecords,
                            'records_to_anonymize' => $recordsToAnonymize,
                            'percentage'           => $percentage,
                        ];
                    }
                }
            } catch (Exception $e) {
                $io->error(sprintf('Error processing entity manager %s: %s', $managerName, $e->getMessage()));

                return self::FAILURE;
            }
        }

        // Display summary
        if (!empty($allAnonymizers)) {
            $io->section('Summary');
            $io->writeln(sprintf('Total Anonymizers: <info>%d</info>', count($allAnonymizers)));

            // Group by entity
            $byEntity = [];
            foreach ($allAnonymizers as $anonymizer) {
                $key = $anonymizer['connection'] . '::' . $anonymizer['entity'];
                if (!isset($byEntity[$key])) {
                    $byEntity[$key] = [
                        'connection'         => $anonymizer['connection'],
                        'entity'             => $anonymizer['entity'],
                        'table'              => $anonymizer['table'],
                        'total_records'      => $anonymizer['total_records'],
                        'properties'         => 0,
                        'total_to_anonymize' => 0,
                    ];
                }
                ++$byEntity[$key]['properties'];
                $byEntity[$key]['total_to_anonymize'] += $anonymizer['records_to_anonymize'];
            }

            $io->writeln('');
            $io->writeln('<comment>By Entity:</comment>');
            foreach ($byEntity as $entityInfo) {
                $io->writeln(sprintf(
                    '  <info>%s</info> (%s): %d properties, %d records to anonymize / %d total',
                    $entityInfo['entity'],
                    $entityInfo['table'],
                    $entityInfo['properties'],
                    $entityInfo['total_to_anonymize'],
                    $entityInfo['total_records'],
                ));
            }
        }

        return self::SUCCESS;
    }
}
