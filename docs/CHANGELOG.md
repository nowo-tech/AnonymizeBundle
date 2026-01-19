# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.0.13] - 2026-01-19

### Added

- **New Fakers**: Added 6 new faker types (Phase 1 completion - 100%)
  - `FileFaker`: Generate anonymized file paths and names with extension, directory, and absolute path options
  - `JsonFaker`: Generate anonymized JSON structures with schema, depth, and max_items options
  - `TextFaker`: Generate anonymized text content (sentences, paragraphs) with type, min_words, and max_words options
  - `EnumFaker`: Generate values from a predefined list with values and weighted probability options
  - `CountryFaker`: Generate anonymized country codes/names with format (code/name/iso2/iso3) and locale options
  - `LanguageFaker`: Generate anonymized language codes/names with format (code/name) and locale options
  - All new fakers registered in FakerType enum and FakerFactory
  - Total fakers available: 29 (8 original + 21 new)
  - Phase 1 of roadmap: 100% complete (21/21 fakers implemented)
  - Comprehensive test coverage for all new fakers

### Improved

- **EmailFaker**: Enhanced with new options
  - `domain`: Custom domain option
  - `format`: 'name.surname' or 'random' format option
  - `local_part_length`: Control local part length
  - Backward compatible with existing usage

- **PhoneFaker**: Enhanced with new options
  - `country_code`: Specific country code option (e.g., '+34')
  - `format`: 'international' or 'national' format option
  - `include_extension`: Include phone extension option
  - Backward compatible with existing usage

- **CreditCardFaker**: Enhanced with new options
  - `type`: 'visa', 'mastercard', 'amex', or 'random' option
  - `valid`: Generate valid Luhn numbers option
  - `formatted`: Include spaces/dashes in card number option
  - Backward compatible with existing usage

- **Tests**: Added comprehensive test suites for all enhanced fakers
  - 187 tests executed
  - 435 assertions
  - All tests pass

- **Documentation**: Updated README, CONFIGURATION, ROADMAP, and UPGRADING guides
  - Phase 1 marked as 100% complete
  - All new fakers documented
  - Enhanced faker options documented

## [0.0.12] - 2026-01-19

### Added

- **New Fakers**: Added 9 new faker types (Phase 1 continued implementation)
  - `PasswordFaker`: Generate anonymized passwords with length, special chars, numbers, and uppercase options
  - `IpAddressFaker`: Generate anonymized IP addresses (IPv4/IPv6) with version and type (public/private/localhost) options
  - `MacAddressFaker`: Generate anonymized MAC addresses with separator and uppercase options
  - `UuidFaker`: Generate anonymized UUIDs (v1/v4) with version and format options
  - `HashFaker`: Generate anonymized hash values (MD5, SHA1, SHA256, SHA512) with algorithm and length options
  - `CoordinateFaker`: Generate anonymized GPS coordinates with format, precision, and bounds options
  - `ColorFaker`: Generate anonymized color values (hex, rgb, rgba) with format and alpha options
  - `BooleanFaker`: Generate anonymized boolean values with true_probability option
  - `NumericFaker`: Generate anonymized numeric values (int/float) with type, min, max, and precision options
  - All new fakers registered in FakerType enum and FakerFactory
  - Total fakers available: 23 (8 original + 15 new)
  - Comprehensive test coverage for all new fakers

### Improved

- **Documentation**: Updated README and CONFIGURATION guides with new faker types
- **Roadmap**: Updated progress tracking (Phase 1: 71% complete - 15/21 fakers)
- **Tests**: Added complete test suites for all new fakers (148 tests, 341 assertions)
- **Code Coverage**: 45.80% line coverage (414/904 lines), 52.78% class coverage (19/36 classes)
- **Service Registration**: Fixed MaskingFaker service registration issue

## [0.0.11] - 2026-01-19

### Added

- **New Fakers**: Added 6 new faker types (Phase 1 implementation)
  - `AddressFaker`: Generate anonymized street addresses with country, format, and postal code options
  - `DateFaker`: Generate anonymized dates with min/max date, format, and type (past/future/between) options
  - `UsernameFaker`: Generate anonymized usernames with length, prefix, suffix, and number options
  - `UrlFaker`: Generate anonymized URLs with scheme, domain, and path options
  - `CompanyFaker`: Generate anonymized company names with type (corporation/llc/inc) and suffix options
  - `MaskingFaker`: Partial masking of sensitive data with preserve_start, preserve_end, mask_char options
  - All new fakers registered in FakerType enum and FakerFactory
  - Total fakers available: 14 (8 original + 6 new)

- **Demo Enhancements**: Added 4 new entities and fixtures
  - `Product` entity: Demonstrates name, url, date fakers (10 products)
  - `Order` entity: Demonstrates service, address, date, email fakers with patterns (13 orders)
  - `Invoice` entity: Demonstrates masking, company, iban, service fakers (8 invoices)
  - `Employee` entity: Demonstrates username, date, company fakers with exclusion patterns (12 employees)
  - All entities include `AnonymizableTrait` for anonymization tracking
  - Comprehensive fixtures with realistic test data

- **Custom Service Faker**: Added example service in demos
  - `CustomReferenceFaker`: Example service implementing FakerInterface
  - Demonstrates how to create custom anonymizers
  - Used in Customer and Order entities
  - Available in all demo projects (Symfony 6, 7, 8)

### Improved

- **Demo Projects**: Synchronized all demo projects (Symfony 6, 7, 8)
  - Expanded fixtures: 20 users and 25 customers in all demos
  - Added 4 new entities (Product, Order, Invoice, Employee) with fixtures
  - Updated controllers to use SchemaService for anonymized column detection
  - Updated templates with anonymized column alerts and conditional display
  - Added underscore naming strategy to Doctrine configuration
  - Updated bundle version to v0.0.11 in all demos
  - Added complete CRUD interfaces for all entities (Product, Order, Invoice, Employee)
  - Updated navigation menu with all entities organized by categories
  - Added anonymization field alerts in all entity list views
  - Consistent functionality across all Symfony versions
  - Total entities in demos: 6 (User, Customer, Product, Order, Invoice, Employee)
  - Total fixtures in demos: 6 (UserFixtures, CustomerFixtures, ProductFixtures, OrderFixtures, InvoiceFixtures, EmployeeFixtures)

## [0.0.10] - 2026-01-19

### Fixed

- **Doctrine DBAL Compatibility**: Replaced deprecated `quoteIdentifier()` with `quoteSingleIdentifier()`
  - Updated `AnonymizeService` to use `quoteSingleIdentifier()` (4 occurrences)
  - Updated `GenerateAnonymizedColumnCommand` to use `quoteSingleIdentifier()` (2 occurrences)
  - Updated demo controllers to use `quoteSingleIdentifier()` (4 occurrences)
  - Fixes deprecation warnings in Doctrine DBAL 3.x
  - Maintains backward compatibility with Doctrine DBAL 2.x

## [0.0.9] - 2026-01-19

### Fixed

- **AnonymizeService**: Fixed quote() method to handle integer values
  - Convert values to string before quoting in UPDATE queries
  - Fixes error when anonymizing entities with integer IDs
  - Handles both ID columns and update values correctly

- **Demo PostgreSQL Compatibility**: Fixed column name issues
  - Added explicit column names for PostgreSQL compatibility
  - Added underscore naming strategy to Doctrine configuration
  - Ensures consistent column naming across MySQL and PostgreSQL

### Improved

- **Demo Fixtures**: Expanded demo data for better testing
  - UserFixtures: Increased from 5 to 20 users
  - CustomerFixtures: Increased from 8 to 25 customers
  - Added edge cases: null optional fields, age limits (18, 100)
  - Better demonstration of inclusion/exclusion patterns
  - More diverse data scenarios for comprehensive testing

## [0.0.8] - 2026-01-19

### Fixed

- **GitHub Release Workflow**: Fixed release creation to mark as latest
  - Added `make_latest: !isPrerelease` parameter to `createRelease` call
  - New releases are now automatically marked as latest
  - Ensures consistency between release creation and update workflows

### Improved

- **Documentation**: Enhanced upgrade guide
  - Added complete upgrade instructions for v0.0.7
  - Updated compatibility table with all versions
  - Improved documentation consistency

## [0.0.7] - 2026-01-19

### Added

- **SchemaService**: New service for checking database schema information
  - `hasAnonymizedColumn()` method to check if anonymized column exists
  - `hasColumn()` generic method to check any column existence
  - Service is autowired and available for dependency injection
  - Comprehensive test coverage with 8 test cases

### Changed

- **Demo Controllers**: Refactored to use SchemaService
  - Removed duplicate `hasAnonymizedColumn()` method from controllers
  - Controllers now inject SchemaService via dependency injection
  - Improved code organization and reusability

### Fixed

- **Demo Templates**: All texts translated to English
  - Alert messages now in English
  - Consistent language across all demo templates

### Improved

- **Documentation**: Enhanced demo README with anonymized column tracking
  - Added information about `AnonymizableTrait`
  - Added instructions for generating migrations
  - Complete documentation of anonymized column feature

## [0.0.6] - 2026-01-19

### Fixed

- **Symfony 8 Compatibility**: Fixed doctrine-bundle version constraint
  - Updated `doctrine/doctrine-bundle` constraint from `^2.8` to `^2.8 || ^3.0`
  - Symfony 8 requires doctrine-bundle 3.x
  - Maintains backward compatibility with Symfony 6/7 (doctrine-bundle 2.x)
  - Demo projects updated to handle missing `anonymized` column gracefully
  - Controllers now check column existence before using it to prevent SQL errors

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
