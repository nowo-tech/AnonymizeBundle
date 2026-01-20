<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Command;

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command to generate MongoDB scripts for adding the `anonymized` field to anonymizable documents.
 *
 * This command generates JavaScript scripts (compatible with mongosh) to add the `anonymized`
 * boolean field to all documents in collections that should have this field.
 *
 * Note: MongoDB ODM support is planned for future releases. This command currently works by
 * scanning PHP document classes or accepting manual collection names.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[AsCommand(
    name: 'nowo:anonymize:generate-mongo-field',
    description: 'Generate MongoDB script to add anonymized field to anonymizable documents',
    help: <<<'HELP'
The <info>%command.name%</info> command generates JavaScript scripts (compatible with mongosh)
to add the <comment>anonymized</comment> boolean field to all documents in MongoDB collections.

<info>⚠️  Note:</info> MongoDB ODM support is planned for future releases. This command currently
works by scanning PHP document classes or accepting manual collection names.

<info>Examples:</info>

Generate script for specific collections:

<info>php %command.full_name% --collection=user_activities --collection=users</info>

Scan document classes automatically:

<info>php %command.full_name% --scan-documents</info>

Specify database and save to file:

<info>php %command.full_name% --database=myapp --collection=user_activities --output=migration.js</info>

Execute the generated script:

<info>mongosh < mongodb_url > migration.js</info>

Or:

<info>mongosh "mongodb://localhost:27017/anonymize_demo" < migration.js</info>
HELP
)]
final class GenerateMongoAnonymizedFieldCommand extends Command
{
    /**
     * Creates a new GenerateMongoAnonymizedFieldCommand instance.
     *
     * @param ContainerInterface $container The service container
     */
    public function __construct(
        private ContainerInterface $container
    ) {
        parent::__construct();
    }

    /**
     * Configures the command.
     */
    protected function configure(): void
    {
        $this
            ->addOption(
                'database',
                'd',
                InputOption::VALUE_REQUIRED,
                'MongoDB database name (default: anonymize_demo)'
            )
            ->addOption(
                'collection',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'MongoDB collection name(s) to process (can be used multiple times)'
            )
            ->addOption(
                'scan-documents',
                null,
                InputOption::VALUE_NONE,
                'Scan PHP document classes for #[Anonymize] attribute and collection names'
            )
            ->addOption(
                'document-path',
                null,
                InputOption::VALUE_REQUIRED,
                'Path to scan for document classes (default: src/Document)'
            )
            ->addOption(
                'output',
                'o',
                InputOption::VALUE_OPTIONAL,
                'Output file path for the MongoDB script (default: prints to console)'
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
        $io->title('Generate MongoDB Anonymized Field Script');

        $database = $input->getOption('database') ?? 'anonymize_demo';
        $collections = $input->getOption('collection');
        $scanDocuments = $input->getOption('scan-documents');
        $documentPath = $input->getOption('document-path');
        $outputFile = $input->getOption('output');

        // If scanning documents, try to find collections
        if ($scanDocuments) {
            $io->section('Scanning document classes...');
            $foundCollections = $this->scanDocumentClasses($documentPath);

            if (!empty($foundCollections)) {
                $collections = array_merge($collections, $foundCollections);
                $collections = array_unique($collections);
                $io->success(sprintf('Found %d collection(s): %s', count($collections), implode(', ', $collections)));
            } else {
                $io->warning('No collections found in document classes. Use --collection option to specify manually.');
            }
        }

        // If no collections specified, show warning
        if (empty($collections)) {
            $io->error('No collections specified. Use --collection option or --scan-documents to find collections automatically.');
            $io->note('Example: php bin/console nowo:anonymize:generate-mongo-field --collection=user_activities');
            return Command::FAILURE;
        }

        // Generate JavaScript script
        $script = $this->generateMongoScript($database, $collections);

        if ($outputFile !== null) {
            file_put_contents($outputFile, $script);
            $io->success(sprintf('MongoDB script saved to: %s', $outputFile));
            $io->note(sprintf('Execute with: mongosh "mongodb://localhost:27017/%s" < %s', $database, $outputFile));
        } else {
            $io->section('Generated MongoDB Script:');
            $io->text($script);
            $io->note('Use --output option to save to a file');
            $io->note(sprintf('Execute with: mongosh "mongodb://localhost:27017/%s" < script.js', $database));
        }

        return Command::SUCCESS;
    }

    /**
     * Scans PHP document classes for #[Anonymize] attribute and collection names.
     *
     * @param string|null $documentPath The path to scan (default: src/Document)
     * @return array<string> Array of collection names found
     */
    private function scanDocumentClasses(?string $documentPath): array
    {
        $collections = [];

        // Try to determine project root
        $projectRoot = $this->getProjectRoot();
        if ($projectRoot === null) {
            return $collections;
        }

        $scanPath = $documentPath ?? $projectRoot . '/src/Document';
        if (!is_dir($scanPath)) {
            return $collections;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($scanPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            if ($content === false) {
                continue;
            }

            // Look for #[Anonymize] attribute (even if commented)
            if (preg_match('/#\[Anonymize\]/', $content) || preg_match('/@Anonymize/', $content)) {
                // Try to find collection name
                // Look for: #[MongoDB\Document(collection: 'collection_name')]
                // Or: @MongoDB\Document(collection="collection_name")
                if (preg_match('/collection\s*[=:]\s*[\'"]?([^\'"\s,)]+)[\'"]?/', $content, $matches)) {
                    $collections[] = $matches[1];
                } elseif (preg_match('/Document\(collection\s*=\s*[\'"]?([^\'"\s,)]+)[\'"]?/', $content, $matches)) {
                    $collections[] = $matches[1];
                }
            }
        }

        return $collections;
    }

    /**
     * Gets the project root directory.
     *
     * @return string|null The project root or null if not found
     */
    private function getProjectRoot(): ?string
    {
        // Try to get from container (Symfony kernel project dir)
        try {
            if ($this->container->has('kernel')) {
                $kernel = $this->container->get('kernel');
                if (method_exists($kernel, 'getProjectDir')) {
                    return $kernel->getProjectDir();
                }
            }
        } catch (\Exception $e) {
            // Ignore
        }

        // Fallback: try to find composer.json
        $dir = __DIR__;
        for ($i = 0; $i < 10; $i++) {
            if (file_exists($dir . '/composer.json')) {
                return $dir;
            }
            $parent = dirname($dir);
            if ($parent === $dir) {
                break;
            }
            $dir = $parent;
        }

        return null;
    }

    /**
     * Generates a MongoDB JavaScript script to add the anonymized field.
     *
     * @param string $database The database name
     * @param array<string> $collections The collection names
     * @return string The generated JavaScript script
     */
    private function generateMongoScript(string $database, array $collections): string
    {
        $script = <<<'JS'
            // MongoDB Script to Add Anonymized Field
            // Generated: {DATE}
            // Database: {DATABASE}
            // Collections: {COLLECTIONS}

            // Switch to the target database
            use('{DATABASE}');

            // Process each collection
            {COLLECTION_SCRIPTS}

            print('✅ Anonymized field migration completed successfully!');
            JS;

        $collectionScripts = [];
        foreach ($collections as $collection) {
            $collectionScripts[] = <<<JS
                // Add anonymized field to collection: {$collection}
                print('Processing collection: {$collection}...');
                const result{$collection} = db.{$collection}.updateMany(
                    { anonymized: { \$exists: false } },
                    { \$set: { anonymized: false } }
                );
                print(`  ✓ Updated \${result{$collection}.modifiedCount} document(s) in {$collection}`);
                JS;
        }

        $script = str_replace('{DATE}', date('Y-m-d H:i:s'), $script);
        $script = str_replace('{DATABASE}', $database, $script);
        $script = str_replace('{COLLECTIONS}', implode(', ', $collections), $script);
        $script = str_replace('{COLLECTION_SCRIPTS}', implode("\n\n", $collectionScripts), $script);

        return $script;
    }
}
