# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- TBD

## [0.0.23] - 2026-01-21

### Added

- **Standardized Faker API**: All fakers now receive `original_value` parameter
  - `FakerInterface` updated to document `original_value` as standard parameter
  - All fakers receive the original database value automatically
  - Enables more intelligent fakers that can use original values when needed
  - Improves versatility and extensibility for custom fakers

- **Improved PreFlightCheckService**: Enhanced column existence checking
  - Now queries actual database columns instead of relying solely on Doctrine mapping
  - Case-insensitive matching for better compatibility
  - More informative error messages showing available columns
  - Helps identify mapping issues (e.g., firstname vs firstName)

### Changed

- **Symfony Version Requirement**: Minimum Symfony version increased to 6.1+
  - Required for `#[Autowire]` attribute support in constructor parameters
  - All fakers now use `#[Autowire('%nowo_anonymize.locale%')]` attribute
  - Symfony 6.0 is no longer supported
  - Updated `composer.json`, documentation, and demo projects

- **AnonymizeService**: Always passes `original_value` to all fakers
  - Standardized API: all fakers receive `original_value` automatically
  - Backward compatibility: `hash_preserve` and `masking` still support `value` key
  - Fakers can use or ignore the original value as needed

- **HashPreserveFaker and MaskingFaker**: Support both `original_value` and `value`
  - Accept `original_value` (standard) or `value` (backward compatibility)
  - Prioritize `original_value` if available

### Fixed

- **PreFlightCheckService**: Fixed column name detection
  - Now queries actual database schema instead of relying on mapping
  - Better error messages with column suggestions
  - Case-insensitive matching for database compatibility

## [0.0.22] - 2026-01-21

### Fixed

- **Symfony 6.1+ Compatibility**: Fixed services.yaml configuration error
  - Removed autowiring pattern with `exclude` (incompatible with Symfony 6.0, now requires 6.1+)
  - Explicitly defined all 28 fakers that require `locale` parameter
  - Resolved error: "Argument #1 ($resource) must be of type string, array given"
  - Services configuration now works correctly in Symfony 6.1+, 7.0, and 8.0

## [0.0.21] - 2026-01-21

### Added

- **DbalHelper Enhancement**: New `getDriverName()` method for DBAL compatibility
  - Provides cross-version compatibility for getting database driver names
  - Supports DBAL 2.x and 3.x with multiple fallback strategies
  - Extracts driver name from platform class, connection params, or defaults to 'pdo_mysql'
  - Used by `ExportDatabaseCommand` and `DatabaseExportService` for better compatibility

- **Demo UI Improvements**: Enhanced navigation and visual consistency
  - Added breadcrumbs navigation to all CRUD pages (index, show, new, edit)
  - Standardized anonymized badge display with icons and consistent colors
  - Created reusable `_breadcrumbs.html.twig` and `_anonymized_badge.html.twig` components
  - All 156 templates across 3 demos now have consistent navigation and badges

- **MongoDB Fixtures**: Improved fixture loading and data generation
  - Enhanced `load-fixtures.js` script with better error handling
  - Created `reload-mongodb-fixtures.sh` script for manual fixture reloading
  - Improved `entrypoint.sh` for better MongoDB container detection
  - All 5 MongoDB collections now have sample data (125 documents per demo)

### Fixed

- **HashPreserveFaker**: Fixed "requires a 'value' option" error
  - `AnonymizeService` now automatically passes the original value to `HashPreserveFaker`
  - No need to manually specify `value` option in `#[AnonymizeProperty]` attributes
  - Resolved error when anonymizing entities with `hash_preserve` faker type
  - Affects `SystemLog` entity and any entity using `hash_preserve` faker

- **ExportDatabaseCommand**: Fixed DBAL compatibility error
  - Replaced `$connection->getDriver()->getName()` with `DbalHelper::getDriverName()`
  - Resolved "Call to undefined method Driver::getName()" error in Symfony 6.1+/7.0
  - Improved compatibility across different Doctrine DBAL versions
  - `DatabaseExportService` also updated to use the new helper method

### Changed

- **AnonymizeService**: Enhanced faker option handling
  - Automatically injects original value for `hash_preserve` faker type
  - Maintains backward compatibility with existing configurations
  - Improves developer experience by reducing required configuration

- **Demo Templates**: Standardized UI components
  - All anonymized badges now use consistent styling (green check for anonymized, gray X for not anonymized)
  - Bootstrap Icons used for visual clarity
  - Breadcrumbs follow consistent pattern: Home > Entity (connection) > Current Page

## [0.0.20] - 2026-01-20

### Added

- **DbalHelper**: New static helper class for DBAL operations
  - Created `DbalHelper::quoteIdentifier()` static method for database identifier quoting
  - Compatible with both DBAL 2.x and 3.x versions
  - Can be used from anywhere without instantiation
  - Centralized location for DBAL compatibility methods

- **AbstractCommand**: New base class for all bundle commands
  - Provides common functionality for all commands
  - Includes wrapper method for `quoteIdentifier()` for command convenience
  - All 6 commands now extend from `AbstractCommand`

- **Testing Infrastructure**: Added testing script and documentation
  - Created `test-commands.sh` script for automated command testing
  - Added `docs/TESTING_COMMANDS.md` guide for testing all commands
  - Script supports testing in all three demo applications (Symfony 6, 7, 8)

### Changed

- **Code Refactoring**: Improved code organization and reusability
  - Moved `quoteIdentifier()` logic to static `DbalHelper` class
  - All commands now extend `AbstractCommand` instead of `Command` directly
  - `AnonymizeService` now uses `DbalHelper::quoteIdentifier()` directly
  - Eliminated code duplication across commands and services
  - Replaced `Command::SUCCESS/FAILURE` with `self::SUCCESS/FAILURE` for consistency

### Fixed

- **PreFlightCheckService**: Fixed `getEntityManager()` method call error
  - `ClassMetadata` does not have `getEntityManager()` method
  - Updated `checkColumnExistence()` to receive `EntityManagerInterface` as parameter
  - Resolved error: "Call to undefined method Doctrine\ORM\Mapping\ClassMetadata::getEntityManager()"

- **DBAL Compatibility**: Fixed `quoteSingleIdentifier()` compatibility issues
  - Added fallback for DBAL versions that don't have `quoteSingleIdentifier()` method
  - Supports DBAL 2.x (`quoteIdentifier`) and DBAL 3.x (`quoteSingleIdentifier`)
  - Manual fallback with backticks for maximum compatibility

## [0.0.19] - 2026-01-20

### Added

- **Demo Improvements**: Enhanced demo applications with better navigation and user experience
  - Added "Back to List" links in all form pages (new.html.twig and edit.html.twig) for better navigation
  - Added missing links in home page for EmailSubscription, SystemLog, and all MongoDB collections
  - Added SQLite connection links in sidebar navigation for all ORM entities
  - Improved consistency across all three demo applications (Symfony 6, 7, and 8)

### Fixed

- **Demo Navigation**: Fixed missing `connection` parameter in home page links
  - EmailSubscription and SystemLog links now correctly include the `connection` parameter
  - All home page links now follow the same pattern as other entities (MySQL, PostgreSQL, SQLite)
  - Resolved routing error: "Some mandatory parameters are missing ("connection") to generate a URL"

## [0.0.18] - 2026-01-20

### Fixed

- **Symfony 6.1+ Compatibility**: Fixed compatibility issue
  - Moved command help text from `#[AsCommand]` attribute parameter to `setHelp()` method in `configure()`
  - The `help` parameter in `#[AsCommand]` is only available from Symfony 6.1+
  - All commands now use `setHelp()` for maximum compatibility (Symfony 6.1+, 7.0, 8.0)
  - Affected commands: `AnonymizeCommand`, `AnonymizationHistoryCommand`, `ExportDatabaseCommand`, `AnonymizeInfoCommand`, `GenerateAnonymizedColumnCommand`, `GenerateMongoAnonymizedFieldCommand`

- **DatabaseExportService Configuration**: Fixed autowiring issue for `DatabaseExportService`
  - Service now explicitly configured in `services.yaml` with all required parameters
  - Excluded from automatic autowiring to prevent parameter resolution issues
  - All parameters (`outputDir`, `filenamePattern`, `compression`, `autoGitignore`) now correctly resolved from bundle configuration

- **UsernameFaker Overflow Warning**: Fixed PHP warning for float-to-int conversion
  - Limited `remainingLength` to maximum 9 to prevent overflow when calculating `pow(10, n)`
  - Prevents warning: "The float ... is not representable as an int, cast occurred"
  - `pow(10, 9) = 1,000,000,000` is safe for int representation in PHP

- **TextFakerTest Flaky Test**: Improved test robustness for Faker randomness
  - Test now runs 10 iterations to account for Faker's random word generation
  - More lenient assertions that handle Faker's occasional generation of fewer words than requested
  - Test verifies minimum word count across iterations rather than single assertion

## [0.0.17] - 2026-01-20

### Added

- **Interactive Mode**: Step-by-step confirmation prompts for anonymization
  - New `--interactive` or `-i` option for the `nowo:anonymize:run` command
  - Summary display before starting anonymization
  - Confirmation prompts for each entity manager
  - Confirmation prompts for each entity before processing
  - Shows entity details (table name, property count) in interactive mode
  - Verbose mode shows property details (faker types) in interactive prompts
  - Allows selective processing of entity managers and entities
  - Improves safety and user control during anonymization

- **Enhanced Reporting**: Improved statistics and export capabilities
  - New `--stats-csv` option to export statistics to CSV format
  - Success rate calculation and display (global and per-entity)
  - Enhanced statistics tables with success rate column
  - CSV export includes: global statistics, entity statistics, and property statistics
  - Improved statistics display with more detailed metrics
  - CSV format suitable for spreadsheet analysis and reporting
  - Configurable output directory for statistics via `stats_output_dir` configuration
  - Relative file paths automatically use configured output directory
  - Absolute paths are used as-is for maximum flexibility

- **Database Export Command**: Export databases to files with optional compression
  - New `nowo:anonymize:export-db` command for exporting databases
  - Supports MySQL (mysqldump), PostgreSQL (pg_dump), SQLite (file copy), and MongoDB (mongodump)
  - Configurable output directory and filename patterns with placeholders
  - Compression support: gzip, bzip2, zip (auto-detects available tools)
  - Automatic `.gitignore` management to exclude export directory
  - Selective export: export specific connections or all connections
  - Configurable via bundle configuration (`nowo_anonymize.export.*`)
  - Filename pattern placeholders: `{connection}`, `{database}`, `{date}`, `{time}`, `{format}`
  - Command-line options for overriding configuration
  - Detailed export summary with file sizes and success/failure counts

- **Configuration Enhancements**:
  - New `stats_output_dir` configuration option for statistics export directory
  - New `export` configuration section for database export settings
  - All command help text moved to `#[AsCommand]` attributes for better organization
  - Symfony Flex recipe updated with all new configuration options

- **Anonymization History**: Track and manage anonymization runs
  - New `nowo:anonymize:history` command to view and manage anonymization history
  - Automatic saving of anonymization run metadata after each execution
  - List all anonymization runs with filtering options (limit, connection)
  - View detailed information about specific runs
  - Compare two anonymization runs side-by-side
  - Cleanup old runs to manage storage
  - History stored in JSON format with index file for quick access
  - Configurable history directory via `history_dir` configuration option
  - Includes metadata: environment, PHP version, Symfony version, command options
  - Comparison shows differences in entities processed, updated, and duration

## [0.0.16] - 2026-01-20

### Added

- **Relationship Patterns Support**: Patterns can now reference related entities using dot notation
  - Support for relationship patterns in `includePatterns` and `excludePatterns` (e.g., `'type.name' => '%HR'`)
  - Automatic SQL JOIN construction for relationship patterns
  - Works with all pattern operators: comparison (`>`, `<`, `=`, etc.), SQL LIKE (`%`), and OR (`|`)
  - Supports `ManyToOne` and `OneToOne` relationships
  - Example: `#[Anonymize(includePatterns: ['type.name' => '%HR', 'status' => 'completed'])]`
  - The bundle automatically detects relationship patterns and builds appropriate SQL queries with LEFT JOINs
  - PatternMatcher updated to access nested relationship values
  - Documented in `docs/USAGE.md` and `docs/CONFIGURATION.md`

- **Demo: Type Entity and Relationship Example**: New example demonstrating relationship patterns
  - Created `Type` entity (id, name, description) in all demo projects
  - Added `ManyToOne` relationship from `Order` to `Type`
  - Updated `Order` entity with relationship pattern example: `['type.name' => '%HR']`
  - Created `TypeFixtures` with 7 types (HR, HR Management, Sales, IT, Marketing, Finance, Operations)
  - Updated `OrderFixtures` to include type relationships
  - Applied to all demo projects (Symfony 6, 7, 8)

### Improved

- **Demo: MongoDB CRUD Navigation**: Enhanced menu navigation in all demo projects
  - Added MongoDB section to sidebar menu in `base.html.twig`
  - "User Activities (MongoDB)" link now visible in navigation menu
  - Better integration of MongoDB CRUD with other entity CRUDs
  - Applied to all demo projects (Symfony 6, 7, 8)

## [0.0.15] - 2026-01-20

### Added

- **MongoDB Field Migration Command**: New command to generate MongoDB scripts for adding `anonymized` field
  - `nowo:anonymize:generate-mongo-field`: Generate JavaScript scripts (compatible with mongosh) to add `anonymized` field
  - Supports `--scan-documents` to automatically detect MongoDB document classes with `#[Anonymize]` attribute
  - Supports `--collection` option to manually specify collection names
  - Supports `--database` option to specify target database
  - Generates scripts that use `updateMany()` to add `anonymized: false` to existing documents
  - Output can be saved to file with `--output` option
  - Complements the existing SQL migration command (`nowo:anonymize:generate-column-migration`)
  - Documented in `docs/COMMANDS.md` and `README.md`

### Improved

- **Demo: Enhanced CRUD Navigation**: Updated home page in all demo projects to show all available CRUDs
  - Added links for Product, Order, Invoice, Employee entities across all SQL connections (MySQL, PostgreSQL, SQLite)
  - All CRUD interfaces now accessible from home page
  - Better organization of CRUD links by entity type
  - Applied to all demo projects (Symfony 6, 7, 8)

- **Demo: MongoDB Scripts**: Improved MongoDB fixture scripts
  - Updated scripts to use current database connection (already connected via mongosh)
  - Better comments explaining script usage
  - Applied to all demo projects (Symfony 6, 7, 8)

## [0.0.14] - 2026-01-20

### Added

- **Pattern Matching Enhancement**: PatternMatcher now supports multiple values with `|` (OR) operator
  - Allows matching multiple values in a single pattern (e.g., `'status' => 'inactive|unsubscribed'`)
  - Supports SQL LIKE patterns with `%` wildcard combined with OR operator
  - Useful for complex pattern matching scenarios
  - Example: `includePatterns: ['email' => '%@test-domain.com|%@example.com|%@demo.local']`

- **Demo: EmailSubscription Entity**: New entity demonstrating comprehensive pattern-based anonymization
  - Shows how to anonymize emails based on domain patterns
  - Demonstrates conditional anonymization based on status
  - Includes ~50 fixture records covering all pattern combinations
  - Examples of all pattern types: domain matching, status-based conditions, date conditions
  - Applied to all demo projects (Symfony 6, 7, 8)

- **Demo: MongoDB Infrastructure**: Added MongoDB support to all demo projects
  - MongoDB 7.0 service added to docker-compose.yml in all demos
  - Mongo Express added for MongoDB management (ports: 8088/8087/8086)
  - MongoDB connection variables (MONGODB_URL) configured
  - Entrypoint scripts updated to wait for MongoDB readiness
  - Sample document (`UserActivity`) prepared for when bundle supports MongoDB ODM
  - Dockerfiles updated with mongodb-tools
  - Healthchecks configured for MongoDB
  - MongoDB fixtures script (`load-fixtures.js`) loads 30 user activities automatically
  - MongoDB CRUD interface (`/mongodb/user-activity`) for viewing and managing documents
  - `anonymized` field added to MongoDB documents (similar to `AnonymizableTrait` in ORM entities)
  - Applied to all demo projects (Symfony 6, 7, 8)

- **Demo: SQLite Support**: Added SQLite database support to all demo projects
  - SQLite connection configured in doctrine.yaml
  - File-based database at `var/data/anonymize_demo.sqlite`
  - pdo_sqlite extension added to Dockerfiles
  - Automatic setup in entrypoint scripts
  - Same entities and fixtures as MySQL/PostgreSQL
  - Applied to all demo projects (Symfony 6, 7, 8)

### Fixed

- **Entity-Level Pattern Filtering**: Fixed issue where entity-level `includePatterns`/`excludePatterns` were not applied
  - Patterns from `#[Anonymize]` attribute are now correctly applied before processing records
  - Ensures entities like `Order` and `Customer` filter records correctly based on entity-level patterns
  - Example: `Order` now correctly only processes records with `status='completed'` and `id>5`

- **Service Registration**: Fixed `CustomReferenceFaker` service registration in demos
  - Now uses `#[Autoconfigure(public: true)]` attribute instead of explicit YAML configuration
  - More declarative and consistent with bundle patterns
  - Applied to all demo projects (Symfony 6, 7, 8)

- **EventDispatcher Injection**: Fixed optional EventDispatcher injection compatibility
  - Changed from `@?event_dispatcher` to full interface name for better compatibility
  - Prevents configuration loading errors

- **SystemLog Fixtures**: Added missing fixtures for `SystemLog` entity in all demo projects
  - Ensures `SystemLog` table exists and has data for anonymization testing
  - Applied to all demo projects (Symfony 6, 7, 8)

### Improved

- **Demo Coverage**: Enhanced demo fixtures with comprehensive test cases
  - EmailSubscription fixtures expanded to ~50 records
  - Covers all pattern combinations: domain matching, status conditions, with/without backup emails, with/without notes
  - All source types represented: website, newsletter, promotion, partner
  - Different date ranges for comprehensive testing

## [0.0.13] - 2026-01-19

### Added

- **Enhanced Existing Fakers**: Improved IbanFaker, AgeFaker, NameFaker, and SurnameFaker
  - `IbanFaker`: Added `valid` and `formatted` options
  - `AgeFaker`: Added `distribution` (uniform/normal), `mean`, and `std_dev` options
  - `NameFaker`: Added `gender` (male/female/random) and `locale_specific` options
  - `SurnameFaker`: Added `gender` and `locale_specific` options for API consistency
  - All improvements backward compatible

- **New Fakers**: Added 3 new faker types (Phase 2 - Data Preservation Strategies)
  - `HashPreserveFaker`: Deterministic anonymization using hash functions
    - Maintains referential integrity (same input → same output)
    - Options: `algorithm` (md5/sha1/sha256/sha512), `salt`, `preserve_format`, `length`
    - Use cases: When you need to maintain referential integrity
  - `ShuffleFaker`: Shuffle values within a column while maintaining distribution
    - Preserves statistical properties
    - Options: `values` (required), `seed` (for reproducibility), `exclude`
    - Use cases: When statistical properties must be preserved
  - `ConstantFaker`: Replace with constant value
    - Options: `value` (required, can be any type including null)
    - Use cases: Null out sensitive data or replace with fixed values
  - All new fakers registered in FakerType enum and FakerFactory
  - Total fakers available: 32 (29 from v0.0.12 + 3 new)
  - Comprehensive test coverage for all new fakers

- **Pre-flight Checks**: Comprehensive validation before anonymization execution
  - Database connectivity validation
  - Entity existence validation
  - Column existence validation
  - Faker type and options validation
  - Pattern validation (include/exclude)
  - Clear error messages for all validation failures
  - Integrated in `AnonymizeCommand` before processing
  - New `PreFlightCheckService` for centralized validation

- **Progress Bars**: Visual progress indicators for anonymization process
  - Real-time progress bars using Symfony Console ProgressBar
  - Shows percentage, elapsed time, and estimated time
  - Displays current status message
  - Updates every 1% of progress
  - Option `--no-progress` to disable progress bars
  - Compatible with `--stats-only` mode
  - Progress callback system in `AnonymizeService`

- **Enhanced Environment Protection**: Improved safety checks
  - New `EnvironmentProtectionService` for comprehensive environment validation
  - Validates environment (dev/test only)
  - Validates debug mode
  - Validates configuration files (detects production config)
  - Validates bundle registration in `bundles.php`
  - Clear error messages with actionable guidance
  - Integrated in both `AnonymizeCommand` and `GenerateAnonymizedColumnCommand`

- **Debug and Verbose Modes**: Enhanced output options
  - `--verbose, -v`: Increase verbosity of messages
  - `--debug`: Enable debug mode with detailed information
  - Shows detailed entity information in verbose mode
  - Shows property details, patterns, and options in debug mode
  - Shows pre-flight check information
  - Shows total records per table
  - Shows property statistics after processing
  - Compatible with Symfony Console verbosity system

- **Info Command**: New command to display anonymizer information
  - `nowo:anonymize:info`: Display information about anonymizers
  - Shows location of each anonymizer (entity and property)
  - Shows configuration (faker type, options, patterns)
  - Shows execution order (based on weight)
  - Shows statistics about how many records will be anonymized
  - Options: `--connection`, `--locale`

- **Event System**: Symfony events for extensibility
  - `BeforeAnonymizeEvent`: Dispatched before anonymization starts
  - `AfterAnonymizeEvent`: Dispatched after anonymization completes
  - `BeforeEntityAnonymizeEvent`: Dispatched before processing each entity
  - `AfterEntityAnonymizeEvent`: Dispatched after processing each entity
  - `AnonymizePropertyEvent`: Dispatched before anonymizing each property
  - Allows listeners to modify anonymized values or skip anonymization
  - Supports event listeners and subscribers
  - EventDispatcher is optional (works without it)

- **Demo Coverage**: Complete faker examples in all demos
  - New `SystemLog` entity demonstrating all remaining fakers
  - Demonstrates: password, ip_address, mac_address, uuid, hash, coordinate, color, boolean, numeric, file, json, text, enum, country, language, hash_preserve, shuffle, constant
  - All 32 fakers now have examples in demos (100% coverage)
  - Updated in all demo projects (Symfony 6, 7, 8)

### Improved

- **Services Configuration**: Optimized `services.yaml` for better maintainability
  - Removed 32 redundant explicit alias definitions
  - Aliases now created automatically from `#[AsAlias]` attributes
  - Fakers without locale parameter use `#[Autoconfigure(public: true)]` attribute
  - Reduced YAML from 89 to 52 lines (35% reduction)
  - More declarative configuration in PHP classes

- **Demo Templates**: Enhanced conditional display of anonymized column
  - Column `anonymized` now displayed conditionally in all list views
  - Only shown when column exists in database (checked via `SchemaService`)
  - Visual indicators: ✓ Yes (green) for anonymized, ✗ No (gray) for not anonymized
  - Updated in all 18 templates across Symfony 6, 7, and 8 demos
  - Prevents errors when column doesn't exist

- **Code Quality**: Improved service registration
  - Fakers use `#[Autoconfigure(public: true)]` instead of YAML configuration
  - Configuration moved from YAML to PHP attributes (more maintainable)
  - Consistent pattern across all fakers

- **Command Options**: Enhanced command-line interface
  - Better error messages and warnings
  - More informative output in verbose/debug modes
  - Improved user experience with progress indicators

- **Safety**: Enhanced protection against accidental production execution
  - Multiple layers of environment validation
  - Configuration file validation
  - Clear warnings and error messages

- **Developer Experience**: Better debugging and monitoring
  - Detailed information in debug mode
  - Progress tracking for long-running operations
  - Comprehensive validation feedback

- **Tests**: Enhanced test coverage for improved fakers
  - 216 tests, 512 assertions - All passing
  - Comprehensive tests for all enhanced faker options

## [0.0.12] - 2026-01-19

### Added

- **New Fakers**: Added 15 new faker types (Phase 1 completion - 100%)
  - **Phase 1 continued (9 fakers)**: `PasswordFaker`, `IpAddressFaker`, `MacAddressFaker`, `UuidFaker`, `HashFaker`, `CoordinateFaker`, `ColorFaker`, `BooleanFaker`, `NumericFaker`
  - **Phase 1 final (6 fakers)**: `FileFaker`, `JsonFaker`, `TextFaker`, `EnumFaker`, `CountryFaker`, `LanguageFaker`
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

- **Tests**: Added comprehensive test suites for all new and enhanced fakers
  - 187 tests executed
  - 435 assertions
  - All tests pass

- **Documentation**: Updated README, CONFIGURATION, ROADMAP, and UPGRADING guides
  - Phase 1 marked as 100% complete
  - All new fakers documented
  - Enhanced faker options documented

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
  - `demo-symfony6` - Demo with Symfony 6.1+, MySQL and PostgreSQL connections
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
