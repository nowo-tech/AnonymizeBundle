# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.0.6] - 2026-01-19

### Fixed

- **Symfony 8 Compatibility**: Fixed doctrine-bundle version constraint
  - Updated `doctrine/doctrine-bundle` constraint from `^2.8` to `^2.8 || ^3.0`
  - Symfony 8 requires doctrine-bundle 3.x
  - Maintains backward compatibility with Symfony 6/7 (doctrine-bundle 2.x)

## [0.0.5] - 2026-01-19

### Changed

- **Doctrine Bundle Compatibility**: Improved compatibility with Symfony 8
  - Updated `doctrine/doctrine-bundle` constraint from `^2.15` to `^2.8`
  - Allows broader compatibility across Symfony 6, 7, and 8
  - Symfony Flex can now resolve compatible versions automatically

- **Demo Projects**: Updated demo configurations
  - Symfony 8 demo now uses `dev-main` for bundle development
  - Improved dependency resolution for Symfony 8 compatibility
  - Removed explicit constraints that conflicted with Symfony 8

## [0.0.4] - 2026-01-19

### Added

- **Anonymized Column Tracking**: Added functionality to track anonymization status in database
  - `AnonymizableTrait`: Trait that adds an `anonymized` boolean field to entities
  - `nowo:anonymize:generate-column-migration` command: Generates SQL migrations to add the `anonymized` column
  - `AnonymizeService`: Automatically sets `anonymized = true` when a record is anonymized
  - Automatic column detection: Checks if the `anonymized` column exists before updating
  - Demo examples: All demo entities (User, Customer) now use `AnonymizableTrait`

### Changed

- **AnonymizeService**: Enhanced to detect and update `anonymized` column
  - Checks if entity uses `AnonymizableTrait` before setting the flag
  - Verifies column existence in database schema before updating
  - Sets `anonymized = true` automatically during anonymization process

## [0.0.3] - 2026-01-19

### Added

- **Comprehensive Test Suite**: Added unit tests for all Faker classes and services
  - Tests for EmailFaker, NameFaker, SurnameFaker, PhoneFaker, IbanFaker, CreditCardFaker
  - Tests for FakerFactory with all faker types
  - Tests for ServiceFaker with different service implementations
  - Comprehensive tests for PatternMatcher with all operators
  - Total: 51 tests with 119 assertions

- **Faker Services**: Registered all fakers as Symfony services
  - All fakers are now singleton services with locale injection
  - Fakers can be injected directly where needed
  - Better integration with Symfony dependency injection

- **Service Aliases**: Added `#[AsAlias]` attributes to all faker classes
  - Modern Symfony 6.3+ approach for service aliases
  - Aliases defined directly in classes
  - Cleaner services.yaml configuration

- **Demo Projects**: Added WebProfilerBundle to all demo projects
  - Symfony WebProfilerBundle for development debugging
  - Available in dev and test environments

### Changed

- **ContainerInterface Usage**: Unified to PSR-11 standard
  - All services now use `Psr\Container\ContainerInterface`
  - Removed Symfony-specific ContainerInterface usage
  - More portable and standard-compliant

- **FakerFactory**: Updated to use services from container
  - Tries to get fakers from service container first
  - Falls back to direct instantiation if container is not available
  - Uses alias IDs defined via `#[AsAlias]` attributes

### Fixed

- **ServiceFaker Autowiring**: Fixed autowiring error for ServiceFaker
  - Added `#[Exclude]` attribute to prevent automatic service registration
  - ServiceFaker is created dynamically by FakerFactory
  - Excluded from services.yaml resource registration

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
