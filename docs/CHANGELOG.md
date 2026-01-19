# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.0.2] - 2026-01-19

### Fixed

- **Autowiring Configuration**: Fixed ContainerInterface autowiring issue
  - Added alias for `Symfony\Component\DependencyInjection\ContainerInterface` to `@service_container`
  - Added alias for `Psr\Container\ContainerInterface` to `@service_container`
  - Optimized services configuration to use autowiring whenever possible
  - Removed manual service configuration in favor of autowiring

### Changed

- **Services Configuration**: Optimized `services.yaml` to maximize autowiring
  - Simplified service definitions
  - Removed unnecessary manual configurations
  - All services now use autowiring by default

## [0.0.1] - 2026-01-19

### Added

- **Initial Release**: Complete database anonymization functionality for Symfony
  - Attribute-based entity and property configuration (`#[Anonymize]` and `#[AnonymizeProperty]`)
  - Automatic entity discovery across multiple Doctrine connections
  - Support for multiple database types (MySQL, PostgreSQL)
  - Comprehensive Faker integration with custom types
  - Weight-based processing order
  - Pattern matching for selective anonymization
  - Statistics collection and reporting
  - Dry-run mode for safe testing
  - Development-only bundle with built-in environment protection

- **Bundle Structure**:
  - `Nowo\AnonymizeBundle` namespace
  - Dependency injection configuration
  - Service definitions with autowiring
  - Console command for anonymization
  - Symfony Flex recipe for automatic setup

- **Attributes**:
  - `#[Anonymize]` - Mark entities for anonymization
  - `#[AnonymizeProperty]` - Configure property anonymization with type, weight, and patterns

- **Faker Types**:
  - `EmailFaker` - Generate anonymized email addresses
  - `NameFaker` - Generate anonymized first names
  - `SurnameFaker` - Generate anonymized last names
  - `AgeFaker` - Generate anonymized ages with configurable ranges
  - `PhoneFaker` - Generate anonymized phone numbers
  - `IbanFaker` - Generate anonymized IBAN codes
  - `CreditCardFaker` - Generate anonymized credit card numbers
  - `ServiceFaker` - Use custom services for anonymization

- **Services**:
  - `AnonymizeService` - Core anonymization logic
  - `PatternMatcher` - Pattern matching for inclusion/exclusion
  - `FakerFactory` - Factory for creating faker instances
  - `AnonymizeStatistics` - Statistics collection and reporting

- **Commands**:
  - `nowo:anonymize:run` - Main anonymization command with options:
    - `--connection` - Process specific connections
    - `--dry-run` - Test mode without making changes
    - `--batch-size` - Configure batch processing size
    - `--locale` - Set Faker locale
    - `--stats-json` - Export statistics to JSON
    - `--stats-only` - Show only statistics summary

- **Security Features**:
  - Built-in environment validation (dev/test only)
  - Automatic command failure in production environments
  - Configuration file created in `config/packages/dev/` by default

- **Demo Projects**: Created three independent demo projects for different Symfony versions
  - `demo-symfony6` - Demo with Symfony 6.0, MySQL and PostgreSQL connections
  - `demo-symfony7` - Demo with Symfony 7.0, MySQL and PostgreSQL connections
  - `demo-symfony8` - Demo with Symfony 8.0, MySQL and PostgreSQL connections
  - Each demo includes:
    - Docker Compose with both database types (MySQL and PostgreSQL)
    - Complete Symfony setup with Nginx and PHP-FPM
    - Example entities (User, Customer) with anonymization attributes
    - CRUD interfaces for managing entities
    - DoctrineFixturesBundle for loading sample data
    - phpMyAdmin and pgAdmin for database visualization
    - Makefile for easy management
    - Comprehensive documentation

- **Development Tools**:
  - Docker setup for development
  - Makefile with common development commands
  - PHP-CS-Fixer configuration (PSR-12)
  - PHPUnit configuration with coverage
  - GitHub Actions CI/CD workflows (ci.yml, release.yml, sync-releases.yml)

- **Documentation**:
  - Comprehensive README.md with usage examples
  - INSTALLATION.md guide
  - CONFIGURATION.md guide
  - CHANGELOG.md (this file)
  - UPGRADING.md guide
  - PHP Doc comments in English for all classes, methods, and properties
  - Demo project documentation

### Changed

- N/A - Initial release

### Fixed

- N/A - Initial release

### Deprecated

- N/A - Initial release

### Removed

- N/A - Initial release

### Security

- **Development-Only Bundle**: Bundle is restricted to dev/test environments only
  - Command automatically fails if executed in production
  - Bundle registration restricted to dev/test in Symfony Flex recipe
  - Configuration file created in `config/packages/dev/` by default
  - All documentation includes security warnings
