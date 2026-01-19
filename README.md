# Anonymize Bundle

[![CI](https://github.com/nowo-tech/anonymize-bundle/actions/workflows/ci.yml/badge.svg)](https://github.com/nowo-tech/anonymize-bundle/actions/workflows/ci.yml) [![Latest Stable Version](https://poser.pugx.org/nowo-tech/anonymize-bundle/v)](https://packagist.org/packages/nowo-tech/anonymize-bundle) [![License](https://poser.pugx.org/nowo-tech/anonymize-bundle/license)](https://packagist.org/packages/nowo-tech/anonymize-bundle) [![PHP Version Require](https://poser.pugx.org/nowo-tech/anonymize-bundle/require/php)](https://packagist.org/packages/nowo-tech/anonymize-bundle) [![GitHub stars](https://img.shields.io/github/stars/nowo-tech/anonymize-bundle.svg?style=social&label=Star)](https://github.com/nowo-tech/anonymize-bundle)

> ⭐ **Found this project useful?** Give it a star on GitHub! It helps us maintain and improve the project.

Symfony bundle for anonymizing database records using Doctrine attributes and Faker generators.

> ⚠️ **Important**: This bundle is **development-only** and should **never** be installed or used in production environments.

## Features

- ✅ Attribute-based anonymization configuration
- ✅ Support for multiple Doctrine connections
- ✅ Multiple faker types (14 total: email, name, surname, age, phone, IBAN, credit card, address, date, username, url, company, masking, custom service)
- ✅ Weight-based anonymization order
- ✅ Pattern-based inclusion/exclusion filters
- ✅ Support for MySQL and PostgreSQL (MongoDB coming soon)
- ✅ Batch processing for large datasets
- ✅ Dry-run mode for testing
- ✅ Anonymization tracking with `AnonymizableTrait` and `anonymized` column

## Installation

> ⚠️ **Important**: This bundle is **development-only**. Always install it as a dev dependency.

```bash
composer require nowo-tech/anonymize-bundle --dev
```

Then, register the bundle in your `config/bundles.php` **only for dev and test environments**:

```php
<?php

return [
    // ...
    Nowo\AnonymizeBundle\AnonymizeBundle::class => ['dev' => true, 'test' => true],
];
```

> ⚠️ **Security**: The bundle will automatically prevent execution in production environments. The command will fail if run outside of `dev` or `test` environments.

## Usage

### Basic Setup

1. **Mark an entity for anonymization** with the `#[Anonymize]` attribute:

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nowo\AnonymizeBundle\Attribute\Anonymize;
use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;

#[ORM\Entity]
#[Anonymize]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[AnonymizeProperty(type: 'email', weight: 1)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[AnonymizeProperty(type: 'name', weight: 2)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    #[AnonymizeProperty(type: 'surname', weight: 3)]
    private ?string $lastName = null;

    #[ORM\Column]
    #[AnonymizeProperty(type: 'age', weight: 4, options: ['min' => 18, 'max' => 100])]
    private ?int $age = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[AnonymizeProperty(type: 'phone', weight: 5)]
    private ?string $phone = null;
}
```

2. **Run the anonymization command**:

```bash
php bin/console nowo:anonymize:run
```

### Advanced Usage

#### Pattern-based Filtering

You can specify which records to anonymize using inclusion/exclusion patterns:

```php
#[ORM\Entity]
#[Anonymize(
    includePatterns: ['status' => 'active'],
    excludePatterns: ['id' => '<=100']
)]
class User
{
    #[ORM\Column(length: 255)]
    #[AnonymizeProperty(
        type: 'email',
        includePatterns: ['role' => 'admin'],
        excludePatterns: ['id' => '=1']
    )]
    private ?string $email = null;
}
```

#### Weight-based Ordering

Properties with lower weights are anonymized first. Properties without weights are processed last, alphabetically:

```php
#[AnonymizeProperty(type: 'email', weight: 1)]      // Processed first
#[AnonymizeProperty(type: 'name', weight: 2)]       // Processed second
#[AnonymizeProperty(type: 'phone')]                 // Processed last (no weight)
```

#### Custom Service Faker

You can use a custom service for anonymization:

```php
#[AnonymizeProperty(type: 'service', service: 'app.custom_anonymizer')]
private ?string $customField = null;
```

The service must implement `FakerInterface` or have a `generate()` method.

#### Multiple Connections

The bundle automatically processes all Doctrine connections. You can also specify specific connections:

```bash
php bin/console nowo:anonymize:run --connection default --connection secondary
```

#### Anonymization Tracking

Track which records have been anonymized using the `AnonymizableTrait`:

```php
use Nowo\AnonymizeBundle\Trait\AnonymizableTrait;

#[ORM\Entity]
#[Anonymize]
class User
{
    use AnonymizableTrait;
    // ... your properties
}
```

**Generate migration for the `anonymized` column**:

```bash
php bin/console nowo:anonymize:generate-column-migration
```

This command will:
1. Scan all entities using `AnonymizableTrait`
2. Check if the `anonymized` column already exists
3. Generate SQL migrations to add the column if missing

**After anonymization**, records are automatically marked with `anonymized = true`. You can query them:

```sql
SELECT * FROM users WHERE anonymized = true;
```

Or check programmatically:

```php
if ($user->isAnonymized()) {
    // This record has been anonymized
}
```

## Requirements

- PHP >= 8.1, < 8.6
- Symfony >= 6.0 || >= 7.0 || >= 8.0
- Doctrine ORM >= 2.13 || >= 3.0
- Doctrine Bundle >= 2.8 || >= 3.0 (3.0 required for Symfony 8)

## Configuration

The bundle works with default settings. If you're using Symfony Flex, the configuration file is automatically created at `config/packages/dev/nowo_anonymize.yaml`. Otherwise, you can configure it manually in `config/packages/dev/nowo_anonymize.yaml`:

```yaml
nowo_anonymize:
    locale: 'en_US'              # Locale for Faker generator
    connections: []              # Specific connections to process (empty = all)
    dry_run: false              # Dry-run mode (default: false)
    batch_size: 100             # Batch size for processing records
```

See [CONFIGURATION.md](docs/CONFIGURATION.md) for detailed configuration options.

## Commands

### Anonymize Command

```bash
php bin/console nowo:anonymize:run [options]

Options:
  --connection, -c    Process only specific connections (can be used multiple times)
  --dry-run          Show what would be anonymized without making changes
  --batch-size, -b   Number of records to process in each batch (default: 100)
  --locale, -l       Locale for Faker generator (default: en_US)
  --stats-json       Export statistics to JSON file
  --stats-only       Show only statistics summary (suppress detailed output)
```

### Generate Column Migration Command

Generate SQL migrations to add the `anonymized` column to entities using `AnonymizableTrait`:

```bash
php bin/console nowo:anonymize:generate-column-migration [options]

Options:
  --connection, -c    Process only specific connections (can be used multiple times)
  --output, -o       Output SQL to a file instead of console
```

**Example**:
```bash
# Generate migrations for all connections
php bin/console nowo:anonymize:generate-column-migration

# Generate migrations for specific connection
php bin/console nowo:anonymize:generate-column-migration --connection default

# Output to file
php bin/console nowo:anonymize:generate-column-migration --output migrations/add_anonymized_column.sql
```

## Statistics

The bundle provides detailed statistics about the anonymization process:

- **Total entities processed**: Number of entities that were scanned
- **Total records processed**: Number of database records processed
- **Total records updated**: Number of records that were anonymized
- **Total records skipped**: Number of records that didn't match patterns
- **Duration**: Time taken to complete the anonymization
- **Average per second**: Processing speed
- **Per-entity statistics**: Detailed stats for each entity
- **Per-property statistics**: Count of anonymizations per property

### Viewing Statistics

Statistics are automatically displayed after the anonymization completes:

```bash
php bin/console nowo:anonymize:run
```

### Export Statistics to JSON

Export statistics to a JSON file for further analysis:

```bash
php bin/console nowo:anonymize:run --stats-json stats.json
```

### Statistics Only Mode

Show only the statistics summary without detailed processing output:

```bash
php bin/console nowo:anonymize:run --stats-only
```

## Faker Types

The bundle supports the following faker types:

### Basic Fakers

- **email**: Generates anonymized email addresses
- **name**: Generates anonymized first names
- **surname**: Generates anonymized surnames
- **age**: Generates anonymized ages (supports `min` and `max` options)
- **phone**: Generates anonymized phone numbers
- **iban**: Generates anonymized IBAN numbers (supports `country` option)
- **credit_card**: Generates anonymized credit card numbers

### Advanced Fakers

- **address**: Generates anonymized street addresses (supports `country`, `format`, `include_postal_code` options)
- **date**: Generates anonymized dates (supports `min_date`, `max_date`, `format`, `type` options)
- **username**: Generates anonymized usernames (supports `min_length`, `max_length`, `prefix`, `suffix`, `include_numbers` options)
- **url**: Generates anonymized URLs (supports `scheme`, `domain`, `path` options)
- **company**: Generates anonymized company names (supports `type`, `suffix` options)
- **masking**: Partial masking of sensitive data (supports `preserve_start`, `preserve_end`, `mask_char`, `mask_length` options)

### Custom Fakers

- **service**: Uses a custom service for anonymization (requires `service` option with service name)

## Pattern Matching

Patterns support the following operators:

- `>`: Greater than (e.g., `'id' => '>100'`)
- `>=`: Greater than or equal
- `<`: Less than
- `<=`: Less than or equal
- `=`: Equal to
- `!=` or `<>`: Not equal to
- `%`: SQL LIKE pattern (e.g., `'name' => 'John%'`)

## Development

### Using Docker (Recommended)

```bash
# Start the container
make up

# Install dependencies
make install

# Run tests
make test

# Run tests with coverage
make test-coverage

# Run all QA checks
make qa
```

### Without Docker

```bash
composer install
composer test
composer test-coverage
composer qa
```

## Testing

The bundle includes comprehensive tests. All tests are located in the `tests/` directory.

### Running Tests

```bash
# Run all tests
composer test

# Run tests with coverage report
composer test-coverage

# View coverage report
open coverage/index.html
```

## Code Quality

The bundle uses PHP-CS-Fixer to enforce code style (PSR-12).

```bash
# Check code style
composer cs-check

# Fix code style
composer cs-fix
```

## Documentation

- [Installation Guide](docs/INSTALLATION.md) - Step-by-step installation instructions
- [Configuration Guide](docs/CONFIGURATION.md) - Detailed configuration options
- [Changelog](docs/CHANGELOG.md) - Complete version history and changes
- [Upgrade Guide](docs/UPGRADING.md) - Instructions for upgrading between versions
- [Roadmap](docs/ROADMAP.md) - Planned features and future enhancements
- [Branching Strategy](docs/BRANCHING.md) - Git workflow and branching guidelines
- [Contributing Guide](docs/CONTRIBUTING.md) - How to contribute to this project

## License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.

## Contributing

We welcome contributions! Please see [CONTRIBUTING.md](docs/CONTRIBUTING.md) for details on how to contribute to this project.

For information about our Git workflow and branching strategy, see [BRANCHING.md](docs/BRANCHING.md).

## Roadmap

We have an extensive roadmap for future enhancements. See [ROADMAP.md](docs/ROADMAP.md) for details on planned features including:

- **Phase 1 (v0.1.0)**: 20+ new fakers (Address, Date, Company, URL, Username, etc.)
- **Phase 2 (v0.2.0)**: Advanced anonymization strategies (Masking, Hash Preserve, Shuffle)
- **Phase 3 (v0.3.0)**: MongoDB and SQLite support
- **Phase 4 (v0.4.0)**: Enhanced developer experience (CLI improvements, reporting, testing tools)
- **Phase 5 (v0.5.0)**: Enterprise features (GDPR compliance, audit logging, API integration)
- **Phase 6 (v0.6.0)**: Performance and scalability improvements
- **Phase 7 (v0.7.0)**: Security and compliance enhancements
- **Phase 8 (v0.8.0)**: Advanced features (ML integration, workflow automation)

Check out the [full roadmap](docs/ROADMAP.md) for detailed information about upcoming features, priorities, and timelines.

## Author

Created by [Héctor Franco Aceituno](https://github.com/HecFranco) at [Nowo.tech](https://nowo.tech)
