# Anonymize Bundle

[![CI](https://github.com/nowo-tech/anonymize-bundle/actions/workflows/ci.yml/badge.svg)](https://github.com/nowo-tech/anonymize-bundle/actions/workflows/ci.yml) [![Latest Stable Version](https://poser.pugx.org/nowo-tech/anonymize-bundle/v)](https://packagist.org/packages/nowo-tech/anonymize-bundle) [![License](https://poser.pugx.org/nowo-tech/anonymize-bundle/license)](https://packagist.org/packages/nowo-tech/anonymize-bundle) [![PHP Version Require](https://poser.pugx.org/nowo-tech/anonymize-bundle/require/php)](https://packagist.org/packages/nowo-tech/anonymize-bundle) [![GitHub stars](https://img.shields.io/github/stars/nowo-tech/anonymize-bundle.svg?style=social&label=Star)](https://github.com/nowo-tech/anonymize-bundle)

> ⭐ **Found this project useful?** Give it a star on GitHub! It helps us maintain and improve the project.

**Symfony bundle for database anonymization, test data generation, and GDPR compliance.** Anonymize database records using Doctrine attributes and Faker generators. Perfect for development environments, testing, data masking, and privacy compliance.

> 📋 **Compatible with Symfony 6.1+, 7.x, and 8.x** - This bundle requires Symfony 6.1 or higher (Symfony 6.0 is not supported).

## What is this?

This bundle helps you **anonymize sensitive data** in your Symfony applications for:
- 🧪 **Test data generation** - Create realistic anonymized test datasets
- 🔒 **GDPR compliance** - Anonymize personal data for development/testing
- 🎭 **Data masking** - Replace sensitive information with fake but realistic data
- 🚀 **Development safety** - Work with anonymized data instead of real user data
- 📊 **Database anonymization** - Anonymize entire databases or specific entities

> ⚠️ **Important**: This bundle is **development-only** and should **never** be installed or used in production environments. The bundle includes built-in protection to prevent execution in production.

## Quick Search Terms

Looking for: **database anonymization**, **test data generator**, **GDPR compliance**, **data masking**, **Symfony anonymize**, **Doctrine anonymization**, **Faker bundle**, **privacy tools**, **PII anonymization**, **data privacy**, **test fixtures**, **development tools**? You've found the right bundle!

## Features

- ✅ Attribute-based anonymization configuration
- ✅ Support for multiple Doctrine connections
- ✅ Multiple faker types (39 total: email, name, surname, age, phone, IBAN, credit card, address, date, username, url, company, masking, password, ip_address, mac_address, uuid, hash, coordinate, color, boolean, numeric, file, json, text, enum, country, language, hash_preserve, shuffle, constant, dni_cif, name_fallback, html, pattern_based, copy, null, utm, custom service)
- ✅ **FakerType enum** for type-safe faker selection (recommended) - IDE autocompletion and compile-time validation
- ✅ String-based faker types still supported (backward compatible)
- ✅ Weight-based anonymization order
- ✅ Pattern-based inclusion/exclusion filters
- ✅ Support for MySQL and PostgreSQL (MongoDB infrastructure ready in demos, ODM support coming soon)
- ✅ Batch processing for large datasets
- ✅ Dry-run mode for testing
- ✅ Anonymization tracking with `AnonymizableTrait` and `anonymized` column
- ✅ Pre-flight checks: Comprehensive validation before execution
- ✅ Progress bars: Visual progress indicators with real-time updates
- ✅ Enhanced environment protection: Multiple safety layers
- ✅ Debug and verbose modes: Detailed output for debugging
- ✅ Interactive mode: Step-by-step confirmations for safer anonymization
- ✅ Enhanced reporting: Export statistics to JSON/CSV with success rates
- ✅ Database export: Export databases to files with optional compression
- ✅ Configurable output directories: Customize where statistics and exports are saved
- ✅ Table truncation: Empty tables before anonymization with configurable execution order

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

## Quick Start

1. **Mark an entity for anonymization** with the `#[Anonymize]` attribute:

```php
use Nowo\AnonymizeBundle\Attribute\Anonymize;
use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;

#[ORM\Entity]
#[Anonymize]
class User
{
    #[AnonymizeProperty(type: 'email', weight: 1)]
    private ?string $email = null;

    #[AnonymizeProperty(type: 'name', weight: 2)]
    private ?string $firstName = null;
}
```

2. **Run the anonymization command**:

```bash
php bin/console nowo:anonymize:run
```

For detailed usage examples, see [USAGE.md](docs/USAGE.md).

## Requirements

- PHP >= 8.1, < 8.6
- **Symfony >= 6.1** || >= 7.0 || >= 8.0
- Doctrine ORM >= 2.13 || >= 3.0
- Doctrine Bundle >= 2.8 || >= 3.0 (3.0 required for Symfony 8)

> **Note**: This bundle requires **Symfony 6.1 or higher**. Symfony 6.0 is not supported because the bundle uses the `#[Autowire]` attribute for dependency injection, which is only available from Symfony 6.1 onwards.

## Configuration

The bundle works with default settings. 

> ⚠️ **Note**: The configuration file is **only automatically created** when:
> - Installing from Packagist with Symfony Flex
> - **AND** the recipe is published in the Symfony recipes-contrib repository
>
> **Current Status**: The recipe is **not yet published**, so you need to **manually create** the file (see below).

If the configuration file was not created automatically, create it manually at `config/packages/dev/nowo_anonymize.yaml`:

```yaml
nowo_anonymize:
    locale: 'en_US'              # Locale for Faker generator
    connections: []              # Specific connections to process (empty = all)
    dry_run: false              # Dry-run mode (default: false)
    batch_size: 100             # Batch size for processing records
```

See [CONFIGURATION.md](docs/CONFIGURATION.md) for detailed configuration options.

## Commands

The bundle provides six console commands:

- **`nowo:anonymize:run`** - Main anonymization command (supports MySQL, PostgreSQL, SQLite)
- **`nowo:anonymize:history`** - View and manage anonymization history (list, view, compare runs)
- **`nowo:anonymize:export-db`** - Export databases to files with optional compression (MySQL, PostgreSQL, SQLite, MongoDB)
- **`nowo:anonymize:generate-column-migration`** - Generate SQL migrations for `anonymized` column (MySQL, PostgreSQL, SQLite)
- **`nowo:anonymize:generate-mongo-field`** - Generate MongoDB script to add `anonymized` field to documents
- **`nowo:anonymize:info`** - Display information about anonymizers

> **Note**: MongoDB ODM support is planned for future releases. The `nowo:anonymize:run` command currently only processes Doctrine ORM connections. However, you can use `nowo:anonymize:generate-mongo-field` to prepare MongoDB documents with the `anonymized` field.

See [COMMANDS.md](docs/COMMANDS.md) for detailed command documentation and examples.

## Faker Types

The bundle supports **35 faker types** for anonymizing various data types, including:

- **Basic**: email, name, surname, age, phone, IBAN, credit_card
- **Advanced**: address, date, username, url, company, masking, password, ip_address, mac_address, uuid, hash, coordinate, color, boolean, numeric, file, json, text, enum, country, language
- **Data Preservation**: hash_preserve, shuffle, constant
- **Specialized**: dni_cif (Spanish DNI/CIF/NIF), name_fallback (handles nullable related name fields), html (HTML with lorem ipsum, perfect for email signatures)
- **Custom**: service (custom service faker)

See [FAKERS.md](docs/FAKERS.md) for complete list and configuration options.

## Documentation

- [Usage Guide](docs/USAGE.md) - Complete usage examples and patterns
- [Commands](docs/COMMANDS.md) - Detailed command documentation
- [Faker Types](docs/FAKERS.md) - Complete list of all 32 faker types
- [Configuration Guide](docs/CONFIGURATION.md) - Detailed configuration options
- [Installation Guide](docs/INSTALLATION.md) - Step-by-step installation instructions
- [Upgrade Guide](docs/UPGRADING.md) - Instructions for upgrading between versions
- [Testing Guide](docs/TESTING_COMMANDS.md) - How to test all commands with all options
- [Development Guide](docs/DEVELOPMENT.md) - Development setup, testing, and code quality

## Testing

The bundle includes a comprehensive testing script to verify all commands work correctly:

```bash
# Test all commands in all demos
./scripts/test-commands.sh all

# Test in a specific demo
./scripts/test-commands.sh demo-symfony6
```

The script tests **31 different command combinations** covering all main options across all database connections. See [TESTING_COMMANDS.md](docs/TESTING_COMMANDS.md) for details.
- [Changelog](docs/CHANGELOG.md) - Complete version history and changes
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

### Current Status (v0.0.26)

- **Phase 1 Progress**: 100% complete (all 21 fakers implemented)
- **Total Fakers Available**: 32 fakers (all fakers from Phase 1 + Phase 2 data preservation fakers)
- **Test Coverage**: 726 tests, 2011 assertions, 59.57% line coverage (1731/2906 lines)
  - Classes: 73.33% (44/60) - 44 classes with 100% coverage
  - Methods: 75.89% (170/224)
  - Comprehensive test coverage for all fakers, services, commands, events, and attributes
- **Pattern Matching**: Enhanced with `|` (OR) operator support for multiple value matching and relationship patterns (e.g., `'type.name' => '%HR'`)
- **MongoDB Support**: Command to generate scripts for adding `anonymized` field to MongoDB documents
- **Relationship Patterns**: Support for patterns referencing related entities using dot notation with automatic SQL JOIN construction
- **Recent Improvements**: Enhanced test coverage, improved boolean/null handling in SQL queries, better error messages

### Planned Phases

- **Phase 1 (v0.1.0)**: Enhanced fakers (100% complete - all fakers implemented)
- **Phase 2 (v0.2.0)**: Advanced anonymization strategies (Hash Preserve, Shuffle, Relationship Preservation)
- **Phase 3 (v0.3.0)**: MongoDB and SQLite support
- **Phase 4 (v0.4.0)**: Enhanced developer experience (CLI improvements, reporting, testing tools)
- **Phase 5 (v0.5.0)**: Enterprise features (GDPR compliance, audit logging, API integration)
- **Phase 6 (v0.6.0)**: Performance and scalability improvements
- **Phase 7 (v0.7.0)**: Security and compliance enhancements
- **Phase 8 (v0.8.0)**: Advanced features (ML integration, workflow automation)

Check out the [full roadmap](docs/ROADMAP.md) for detailed information about upcoming features, priorities, and timelines.

## Author

Created by [Héctor Franco Aceituno](https://github.com/HecFranco) at [Nowo.tech](https://nowo.tech)
