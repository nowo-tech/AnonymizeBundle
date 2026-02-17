# Upgrade Guide

This guide provides step-by-step instructions for upgrading the Anonymize Bundle between versions.

## General Upgrade Process

1. **Backup your database**: Always backup your database before running anonymization
2. **Check the changelog**: Review [CHANGELOG.md](CHANGELOG.md) for breaking changes in the target version
3. **Update composer**: Run `composer update nowo-tech/anonymize-bundle`
4. **Update configuration**: Apply any configuration changes required for the new version
5. **Clear cache**: Run `php bin/console cache:clear`
6. **Test your application**: Verify that anonymization functionality works as expected

## Upgrade Instructions by Version

### Upgrading to 1.0.10

**Release Date**: 2026-02-17

#### What's Changed

- **Entities with only `anonymizeService`**: If an entity has `#[Anonymize(anonymizeService: 'your_service_id')]` and **no** `#[AnonymizeProperty]` on any field, the command now processes it and calls your service for each record. Previously the entity was skipped with "No properties found". No configuration change needed; existing entities that already had at least one `AnonymizeProperty` are unchanged.

#### Breaking Changes

None. Fully backward compatible.

#### Migration Steps

1. **Update the bundle**:
   ```bash
   composer update nowo-tech/anonymize-bundle
   ```

2. **Clear cache**:
   ```bash
   php bin/console cache:clear
   ```

3. No configuration or code changes required. Entities that use only a custom anonymize service (no property attributes) will now be processed automatically.

### Upgrading to 1.0.9

**Release Date**: 2026-02-16

#### What's Fixed

- **FakerFactory autowiring**: The bundle now injects `FakerFactory` explicitly into `AnonymizeService` and `PreFlightCheckService`, so you no longer get "Cannot autowire ... FakerFactory excluded" when the app or bundle excludes that type from autowiring.
- **Synthetic kernel**: Commands and services no longer depend on the `kernel` service, avoiding "kernel is synthetic" errors in environments like FrankenPHP. Paths use the `kernel.project_dir` parameter when available.
- **`--entity` option**: The short form `-e` was removed to avoid conflict with Symfony's global `--env` (`-e`). Use `--entity` only (e.g. `--entity "App\Entity\User"`).

#### Breaking Changes

None. Fully backward compatible. If you used the short form `-e` for `--entity` in scripts, switch to `--entity`.

#### Migration Steps

1. **Update the bundle**:
   ```bash
   composer update nowo-tech/anonymize-bundle
   ```

2. **Clear cache**:
   ```bash
   php bin/console cache:clear
   ```

3. No configuration changes required. If you had scripts using `-e` for the entity filter, change them to `--entity`.

### Upgrading to 1.0.8

**Release Date**: 2026-02-17

#### What's New

- **`--entity` on `nowo:anonymize:run`**: You can limit anonymization to one or more entity class names (e.g. `--entity "App\Entity\SmsNotification"`). Useful to test a single entity, its `anonymizeService` or event listeners.
- **FakerFactory in app services**: If you inject `Nowo\AnonymizeBundle\Faker\FakerFactory` in your own services, register those services only in dev/test (e.g. in `config/services/dev/services.yaml`) so the container can resolve the dependency. The bundle also exposes the alias `nowo_anonymize.faker_factory` for explicit wiring. See CONFIGURATION.md → "Using FakerFactory in your own services".

#### Breaking Changes

None. Fully backward compatible.

#### Migration Steps

1. **Update the bundle**:
   ```bash
   composer update nowo-tech/anonymize-bundle
   ```

2. **Clear cache**:
   ```bash
   php bin/console cache:clear
   ```

3. No configuration or code changes required. Optional: use `--entity` to run anonymization for specific entities; move any app services that depend on `FakerFactory` to dev/test-only registration if you see "no such service exists".

### Upgrading to 1.0.7

**Release Date**: 2026-02-16

#### What's Fixed

- **Doctrine DBAL 4 / CI**: Identifier quoting now uses the database platform when available, ensuring compatibility with DBAL 4 (e.g. PHP 8.1 CI) where connection-level quoting may not be mockable. No configuration or code changes required for application code.

#### Breaking Changes

None. Fully backward compatible.

#### Migration Steps

1. **Update the bundle**:
   ```bash
   composer update nowo-tech/anonymize-bundle
   ```

2. **Clear cache**:
   ```bash
   php bin/console cache:clear
   ```

3. No configuration or code changes required.

### Upgrading to 1.0.6

**Release Date**: 2026-02-17

#### What's Fixed

- **PostgreSQL**: When using `AnonymizableTrait`, the `anonymized` column is now updated with SQL literals `TRUE`/`FALSE` on PostgreSQL instead of integers `1`/`0`, resolving `SQLSTATE[42804]: Datatype mismatch` for boolean columns. No configuration or code changes required.

#### Breaking Changes

None. Fully backward compatible.

#### Migration Steps

1. **Update the bundle**:
   ```bash
   composer update nowo-tech/anonymize-bundle
   ```

2. **Clear cache**:
   ```bash
   php bin/console cache:clear
   ```

3. No configuration or code changes required. If you use PostgreSQL and had been hitting the boolean column error, anonymization will now succeed.

### Upgrading to 1.0.5

**Release Date**: 2026-02-16

#### What's New

- **Anonymization via custom service (`anonymizeService`)**: You can delegate the anonymization of an entity to a service (e.g. for polymorphic subtypes or custom logic).
  - Add `anonymizeService: 'your_service_id'` to `#[Anonymize]`.
  - The service must implement `Nowo\AnonymizeBundle\Service\EntityAnonymizerServiceInterface` and implement `anonymize($em, $metadata, $record, $dryRun)` returning `[ column => value ]` for columns to update.
  - When `anonymizeService` is set, `AnonymizeProperty` attributes on that entity are ignored; the service is responsible for all updates. See demos: `SmsNotification` + `SmsNotificationAnonymizerService`.

- **Truncate by discriminator (polymorphic entities)**: For entities using Doctrine Single Table Inheritance (STI) or Class Table Inheritance (CTI), when `truncate: true` the bundle now deletes only the rows belonging to that entity's discriminator value instead of truncating the whole table.
  - No configuration change: the bundle detects inheritance from metadata and uses `DELETE FROM table WHERE discriminator_column = value` for polymorphic entities, and full truncate for normal entities.
  - When anonymizing, only records matching the entity's discriminator are loaded and updated.

#### Breaking Changes

None. Fully backward compatible.

#### Migration Steps

1. **Update the bundle**:
   ```bash
   composer update nowo-tech/anonymize-bundle
   ```

2. **Clear cache**:
   ```bash
   php bin/console cache:clear
   ```

3. **Optional**: Use `anonymizeService` for entities that need custom anonymization logic; implement `EntityAnonymizerServiceInterface` and register your service. See [USAGE.md](USAGE.md) and demo entities `SmsNotification` / `SmsNotificationAnonymizerService`.

4. **Optional**: If you use STI/CTI entities with `truncate: true`, no change needed; truncation will now only remove rows for that subtype.

#### What's Fixed

- **Doctrine ORM 3**: Discriminator metadata is read correctly when using Doctrine ORM 3.x (`DiscriminatorColumnMapping`). No action required.
- **UtmFaker**: Campaign generation now respects `min_length`/`max_length` in all cases.

#### Demo fixes (if you run the demos)

- **Notification list/detail pages**: Breadcrumb and notification templates were updated so the list route is always `notification_index` (never `notification_index_index`). No action needed unless you copied demo templates; in that case use `listRoute: 'notification_index'` and `routePrefix: 'notification'` when including `_breadcrumbs.html.twig` for notification pages, and ensure `_breadcrumbs.html.twig` uses the shared `_route` logic (see demo `templates/_breadcrumbs.html.twig`).

### Upgrading to 1.0.4

**Release Date**: 2026-02-04

#### What's New

- **Map Faker**: New faker type for value substitution (if value is X, put Y).
  - Options: `map` (required), `default` (optional). See [FAKERS.md](FAKERS.md).
  - Example: `#[AnonymizeProperty(type: FakerType::MAP, options: ['map' => ['active' => 'status_a', 'inactive' => 'status_b'], 'default' => 'status_unknown'])]`

- **Demos: AnonymizePropertySubscriber**: Example listener for `AnonymizePropertyEvent` (e.g. pre-process files before anonymizing a field), declared with `#[AsEventListener]` (Symfony 6.3+).

#### What's Fixed

- **UtmFaker**: `generateTerm()` now respects `min_length` and `max_length`; previously a single short word could produce a string shorter than `min_length`.

#### Breaking Changes

None. Fully backward compatible.

#### Migration Steps

1. **Update the bundle**:
   ```bash
   composer update nowo-tech/anonymize-bundle
   ```

2. **Clear cache**:
   ```bash
   php bin/console cache:clear
   ```

3. **Optional**: Use the new map faker for status/role-style fields; see demos `FakerTypeExample` (status field) and [FAKERS.md](FAKERS.md).

### Upgrading to 1.0.3

**Release Date**: 2026-02-04

#### What's New

This release extends **excludePatterns** and **includePatterns** with two backward-compatible options:

1. **Array value (OR for one field)**  
   You can use an array of values for a single field instead of `|` in a string.  
   Example: `'email' => ['%@nowo.tech', 'user@example.com']` (equivalent to `'email' => '%@nowo.tech|user@example.com'`).

2. **Multiple configs (OR between configs)**  
   You can pass a list of pattern sets; the record is excluded (or included) when **any** set matches.  
   Example: `excludePatterns: [ ['role' => 'admin'], ['status' => 'deleted'] ]` — exclude when role=admin **or** status=deleted.

#### Breaking Changes

None. Existing single-config and string values (including `|`) continue to work as before.

#### Migration Steps

1. **Update the bundle**:
   ```bash
   composer update nowo-tech/anonymize-bundle
   ```

2. **Clear cache**:
   ```bash
   php bin/console cache:clear
   ```

3. **Optional: adopt new syntax**
   - Use array value for one field: `'field' => ['value1', 'value2']` instead of `'field' => 'value1|value2'`.
   - Use multiple configs: `excludePatterns: [ ['role' => 'admin'], ['status' => 'deleted'] ]` for OR between conditions.

See [CONFIGURATION.md](CONFIGURATION.md#multiple-configs-or-between-sets) and [USAGE.md](USAGE.md) for examples.

### Upgrading to 1.0.2

**Release Date**: 2026-01-27

#### What's New

This release adds support for **table truncation** (emptying tables before anonymization) with configurable execution order.

#### New Features

1. **Table Truncation**: You can now configure entities to have their tables emptied before anonymization using the `truncate` option in `#[Anonymize]`.

2. **Truncation Ordering**: Use `truncate_order` to control the order in which tables are truncated, important for handling foreign key dependencies.

#### Migration Steps

No breaking changes. The new options are optional and default to `false` and `null` respectively.

**Example Usage**:

```php
// Before (no truncation)
#[Anonymize]
class TempData
{
    // ...
}

// After (with truncation)
#[Anonymize(truncate: true, truncate_order: 1)]
class TempData
{
    // ...
}
```

**Key Points**:
- Truncation is executed **BEFORE** anonymization, regardless of entity processing order
- Tables with explicit `truncate_order` are truncated first (lower numbers = earlier)
- Tables without `truncate_order` are truncated alphabetically after explicit orders
- Foreign key constraints are handled automatically (MySQL, PostgreSQL, SQLite)

**Demo Fixtures**:
- `TempDataFixtures`: 8 records of temporary data (emails, names, phones)
- `CacheDataFixtures`: 6 records of cache data with complex JSON values
- `LogEntryFixtures`: 10 records of log entries with messages, IPs, and dates

These fixtures are available in all demo projects (Symfony 6, 7, and 8) and can be loaded using:
```bash
php bin/console doctrine:fixtures:load
```

See [USAGE.md](USAGE.md#truncating-tables-emptying-before-anonymization) for detailed documentation and examples.

### Upgrading to 1.0.1

**Release Date**: 2026-01-27

#### What's New

- **UTM Faker**: New faker type for anonymizing UTM (Urchin Tracking Module) parameters
  - Supports all UTM parameter types: `source`, `medium`, `campaign`, `term`, and `content`
  - Multiple format options: `snake_case`, `kebab-case`, `camelCase`, `lowercase`, `PascalCase`
  - Custom lists support for sources, mediums, and campaigns
  - Prefix and suffix options
  - Configurable min/max length for campaign, term, and content
  - Perfect for anonymizing marketing campaign tracking data
  - Example: `#[AnonymizeProperty(type: FakerType::UTM, options: ['type' => 'source', 'format' => 'snake_case'])]`

- **Example Custom Faker**: Reference implementation for creating custom faker services
  - Located at `src/Faker/Example/ExampleCustomFaker.php`
  - Comprehensive example showing best practices
  - Demonstrates preserving original values, accessing record fields, and using EntityManager
  - Can be copied and adapted for your project
  - Perfect reference for creating custom fakers

- **New Demo Entities**: 
  - `CustomFakerExample`: Demonstrates `ExampleCustomFaker` usage
  - `MarketingCampaign`: Demonstrates `UtmFaker` usage with all parameter types

#### Breaking Changes

None - This is a backward-compatible feature release.

#### Migration Steps

1. **Update the bundle**:
   ```bash
   composer update nowo-tech/anonymize-bundle
   ```

2. **Clear cache**:
   ```bash
   php bin/console cache:clear
   ```

3. **Review new features** (optional):
   - Check `ExampleCustomFaker` at `src/Faker/Example/ExampleCustomFaker.php` for custom faker reference
   - Review `CustomFakerExample` and `MarketingCampaign` entities in demo projects
   - See `USAGE.md` for comprehensive examples of UTM faker and custom fakers

4. **Test your application**:
   - Verify that existing anonymization functionality works as expected
   - If you want to use UTM faker, review the `MarketingCampaign` demo for examples
   - If you want to create custom fakers, use `ExampleCustomFaker` as a reference

#### Migration Notes

This release adds two new features:

1. **UTM Faker**: Use `FakerType::UTM` or `'utm'` as the faker type with `type` option:
   ```php
   #[AnonymizeProperty(
       type: FakerType::UTM,
       options: ['type' => 'source', 'format' => 'snake_case']
   )]
   ```

2. **Example Custom Faker**: Copy `src/Faker/Example/ExampleCustomFaker.php` to your project and adapt it:
   ```php
   // Copy to your project (e.g., src/Service/YourCustomFaker.php)
   // Change namespace to match your project
   // Implement your custom logic
   ```

### Upgrading to 1.0.0

**Release Date**: 2026-01-24

#### What's New

- **Demo: ProtectedUser Entity**: Comprehensive example demonstrating entity-level `excludePatterns`
  - Shows how to exclude entire records from anonymization using multiple exclusion patterns
  - Demonstrates email pattern matching, role-based exclusion, ID ranges, and status-based exclusion
  - Includes 25+ fixture records covering all scenarios
  - Perfect reference for understanding how to protect specific records from anonymization

- **Documentation**: Enhanced documentation for entity-level pattern filtering
  - Clarified how `excludePatterns` work at entity level
  - Added comprehensive examples and use cases

#### Breaking Changes

None - This is a backward-compatible release. The bundle is now considered stable and production-ready for development environments.

#### Migration Steps

1. **Update the bundle**:
   ```bash
   composer update nowo-tech/anonymize-bundle
   ```

2. **Clear cache**:
   ```bash
   php bin/console cache:clear
   ```

3. **Review new demo** (optional):
   - Check `ProtectedUser` entity in demo projects for comprehensive `excludePatterns` examples
   - Review `ProtectedUserFixtures` for detailed exclusion pattern scenarios

4. **Test your application**:
   - Verify that existing anonymization functionality works as expected
   - If you want to use entity-level `excludePatterns`, review the `ProtectedUser` demo for examples

#### Migration Notes

This release marks the **1.0.0 milestone**, indicating that the bundle is stable and feature-complete for development use. All core functionality is well-tested and documented.

If you're using entity-level `excludePatterns`, the `ProtectedUser` demo provides comprehensive examples of:
- Excluding records by email patterns (e.g., `'%@visitor.com'`)
- Excluding records by role (e.g., `'admin'`)
- Excluding records by ID ranges (e.g., `'<=100'`)
- Excluding records by status with OR operator (e.g., `'archived|deleted'`)
- Multiple exclusion patterns working together

### Upgrading to 0.0.29

**Release Date**: 2026-01-24

#### What's New

- **Pattern-Based Faker**: Construct values from other fields while preserving patterns
  - New faker type: `pattern_based`
  - Perfect for fields derived from other fields (e.g., username from email with number suffix)
  - Extracts patterns from original values and appends them to anonymized source field values
  - Options: `source_field` (required), `pattern` (regex), `pattern_replacement`, `separator`, `fallback_faker`, `fallback_options`
  - Example: `#[AnonymizeProperty(type: 'pattern_based', options: ['source_field' => 'email', 'pattern' => '/(\\(\\d+\\))$/'])]`
  - Automatically receives the full record with already anonymized values
  - See [EXAMPLES_PATTERN_BASED.md](EXAMPLES_PATTERN_BASED.md) for detailed examples

- **Copy Faker**: Copy values from other fields
  - New faker type: `copy`
  - Perfect for fields that should be identical after anonymization (e.g., email and emailCanonical)
  - Simply copies the anonymized value from the source field
  - Options: `source_field` (required), `fallback_faker`, `fallback_options`
  - Example: `#[AnonymizeProperty(type: 'copy', options: ['source_field' => 'email'])]`
  - Automatically receives the full record with already anonymized values

- **Demo: UserAccount Entity**: New example entity demonstrating `copy` and `pattern_based` fakers
  - Shows complete workflow: email → username (with pattern) → usernameCanonical (same) → emailCanonical (copy)
  - Available in all demo projects (Symfony 6, 7, 8)

#### Breaking Changes

None - This is a backward-compatible feature release.

#### Migration Steps

1. **Update the bundle**:
   ```bash
   composer update nowo-tech/anonymize-bundle
   ```

2. **Clear cache**:
   ```bash
   php bin/console cache:clear
   ```

3. **Review new fakers** (optional):
   - Check [EXAMPLES_PATTERN_BASED.md](EXAMPLES_PATTERN_BASED.md) for usage examples
   - Review [USAGE.md](USAGE.md) for complete documentation
   - See [FAKERS.md](FAKERS.md) for faker descriptions

4. **Test your application**:
   - Verify that existing anonymization functionality works as expected
   - If you want to use the new fakers, update your entity attributes accordingly

#### Migration Example

If you have fields that are derived from other fields and want to preserve patterns:

**Before** (using separate fakers):
```php
#[AnonymizeProperty(type: 'email', weight: 1)]
public string $email;

#[AnonymizeProperty(type: 'username', weight: 2)]
public string $username;  // Loses pattern from original
```

**After** (using pattern_based):
```php
#[AnonymizeProperty(type: 'email', weight: 1)]
public string $email;

#[AnonymizeProperty(
    type: 'pattern_based',
    weight: 2,
    options: [
        'source_field' => 'email',
        'pattern' => '/(\\(\\d+\\))$/',  // Preserves (15) pattern
        'pattern_replacement' => '$1',
    ]
)]
public string $username;  // Preserves pattern: email@domain.com(15)
```

If you have fields that should be identical:

**Before** (using same faker type):
```php
#[AnonymizeProperty(type: 'email', weight: 1)]
public string $email;

#[AnonymizeProperty(type: 'email', weight: 2)]
public string $emailCanonical;  // May generate different value
```

**After** (using copy):
```php
#[AnonymizeProperty(type: 'email', weight: 1)]
public string $email;

#[AnonymizeProperty(
    type: 'copy',
    weight: 2,
    options: [
        'source_field' => 'email',
    ]
)]
public string $emailCanonical;  // Always same as email
```

### Upgrading to 0.0.28

**Release Date**: 2026-01-23

#### What's New

- **Nullable Option for All Fakers**: Generate `null` values with configurable probability
  - New options: `nullable` (bool) and `null_probability` (0-100)
  - Works with all faker types
  - Useful for simulating optional fields and creating more realistic anonymized datasets
  - Example: `['nullable' => true, 'null_probability' => 30]` generates null 30% of the time
  - When a value is determined to be null, it bypasses faker generation and sets the field to `null` directly
  - Preserves null values during type conversion to prevent null from being converted to empty strings

- **Preserve Null Option for All Fakers**: Skip anonymization when original value is null
  - New option: `preserve_null` (bool)
  - Works with all faker types
  - If `preserve_null` is `true` and the original value is `null`, the field is skipped (not anonymized)
  - If `preserve_null` is `true` and the original value has a value, it is anonymized normally
  - Useful for anonymizing only fields that have values, leaving nulls unchanged
  - Example: `['preserve_null' => true]` - only anonymizes if the field has a value
  - Takes precedence over `nullable` option when original value is null

- **Demo: Contact Entity**: New example entity demonstrating `nullable` and `preserve_null` options
  - Shows how to use both options together with different faker types
  - Includes comprehensive fixtures with 8 records covering all use cases
  - Available in all demo projects (Symfony 6, 7, 8)

#### Breaking Changes

None - This is a backward-compatible feature release.

#### Migration Steps

1. **Update the bundle**:
   ```bash
   composer update nowo-tech/anonymize-bundle
   ```

2. **Clear cache**:
   ```bash
   php bin/console cache:clear
   ```

3. **Optional: Use new options** in your entities:
   ```php
   // Generate null values with 20% probability
   #[AnonymizeProperty(
       type: 'email',
       options: ['nullable' => true, 'null_probability' => 20]
   )]
   private ?string $optionalEmail = null;

   // Only anonymize if field has a value (preserve nulls)
   #[AnonymizeProperty(
       type: 'dni_cif',
       options: ['type' => 'dni', 'preserve_null' => true]
   )]
   private ?string $legalId = null;
   ```

#### Notes

- No configuration changes required
- New options are optional and work with all existing faker types
- Existing anonymization configurations continue to work unchanged
- Both options can be used together for maximum flexibility
- See [USAGE.md](USAGE.md) for detailed examples and use cases

### Upgrading to 0.0.26

**Release Date**: 2026-01-21

#### What's Fixed

- **AnonymizeService**: Fixed boolean and null value handling in SQL UPDATE queries
  - Boolean `false` values were incorrectly converted to empty string `''` instead of `'0'`
  - Boolean `true` values now correctly converted to `'1'`
  - `null` values now correctly converted to SQL `NULL` (unquoted)
  - Resolves `SQLSTATE[HY000]: General error: 1366 Incorrect integer value: '' for column 'is_active'` error
  - Affects MySQL `tinyint` columns and other boolean-type columns

#### What's Changed

- **Demo Projects**: Enhanced Symfony 6 demo Makefile
  - Added `update-symfony` command to help migrate from Symfony 6.0 to 6.1+
  - Updated help text to reflect Symfony 6.1+ requirement
  - Improved documentation for bundle installation process

#### Migration Steps

1. **Update the bundle**:
   ```bash
   composer update nowo-tech/anonymize-bundle
   ```

2. **Clear cache**:
   ```bash
   php bin/console cache:clear
   ```

3. **No configuration changes required**: This is a bug fix release

#### Notes

- This release fixes a critical bug affecting boolean and null value handling in SQL queries
- If you encountered errors with boolean columns (especially `tinyint` in MySQL), this release resolves them
- All functionality remains unchanged, only bug fixes

### Upgrading to 0.0.25

**Release Date**: 2026-01-21

#### What's Fixed

- **Services Configuration**: Fixed FakerFactory autowiring error
  - Resolved "Cannot autowire service AnonymizeService: argument FakerFactory has been excluded" error
  - `FakerFactory` is now explicitly registered in services configuration
  - No functional changes, only fixes a configuration issue

#### Migration Steps

1. **Update the bundle**:
   ```bash
   composer update nowo-tech/anonymize-bundle
   ```

2. **Clear cache**:
   ```bash
   php bin/console cache:clear
   ```

3. **No configuration changes required**: This is a bug fix release

#### Notes

- This release fixes a critical bug in v0.0.24
- If you encountered the FakerFactory autowiring error, this release resolves it
- All functionality remains unchanged

### Upgrading to 0.0.24

**Release Date**: 2026-01-21

#### What's Changed

- **Services Configuration**: Simplified `services.yaml` configuration
  - Now uses autowiring pattern for fakers (cleaner and more maintainable)
  - Reduced configuration complexity while maintaining full functionality
  - All fakers continue to work exactly as before

- **Documentation**: Complete documentation update
  - All documentation now consistently states Symfony 6.1+ requirement
  - Added requirement notes to all major documentation files
  - Updated demo projects documentation

#### What's Fixed

- **Demo Projects**: Fixed Symfony version inconsistencies
  - All demo dependencies now correctly use Symfony 6.1+
  - Updated demo documentation to reflect correct requirements

#### Migration Steps

1. **Update the bundle**:
   ```bash
   composer update nowo-tech/anonymize-bundle
   ```

2. **Clear cache**:
   ```bash
   php bin/console cache:clear
   ```

3. **No configuration changes required**: This release focuses on code cleanup and documentation improvements

#### Notes

- This release is fully backward compatible
- All existing functionality remains unchanged
- Services configuration is now cleaner but works identically
- If you're using Symfony 6.0, you must upgrade to 6.1+ first (see v0.0.23 upgrade guide)

### Upgrading to 0.0.21

**Release Date**: 2026-01-21

#### What's New

- **DbalHelper Enhancement**: New `getDriverName()` method
  - Provides cross-version compatibility for getting database driver names
  - Supports DBAL 2.x and 3.x with multiple fallback strategies
  - Used internally by export commands for better compatibility

- **Demo UI Improvements**: Enhanced navigation and visual consistency
  - Breadcrumbs navigation added to all CRUD pages
  - Standardized anonymized badge display with icons
  - Reusable template components for consistency

- **MongoDB Fixtures**: Improved fixture loading
  - Enhanced fixture scripts with better error handling
  - New reload script for manual fixture management
  - All MongoDB collections now have sample data

#### What's Fixed

- **HashPreserveFaker**: Fixed "requires a 'value' option" error
  - `AnonymizeService` now automatically passes the original value
  - No manual configuration needed for `hash_preserve` faker type
  - Resolves errors when anonymizing entities with hash preservation

- **ExportDatabaseCommand**: Fixed DBAL compatibility error
  - Resolved "Call to undefined method Driver::getName()" error
  - Improved compatibility across different Doctrine DBAL versions
  - Works correctly in Symfony 6.1+, 7.0, and 8.0

#### What's Changed

- **AnonymizeService**: Enhanced faker option handling
  - Automatically injects original value for `hash_preserve` faker
  - Maintains full backward compatibility
  - Improves developer experience

#### Migration Steps

1. **Update the bundle**:
   ```bash
   composer update nowo-tech/anonymize-bundle
   ```

2. **Clear cache**:
   ```bash
   php bin/console cache:clear
   ```

3. **No configuration changes required**: This release focuses on bug fixes and improvements

#### Notes

- This release is fully backward compatible
- All existing functionality remains unchanged
- HashPreserveFaker now works automatically without manual value configuration
- Export commands now work correctly across all DBAL versions

### Upgrading to 0.0.20

**Release Date**: 2026-01-20

#### What's New

- **DbalHelper**: New static helper class for DBAL operations
  - `DbalHelper::quoteIdentifier()` can be used from anywhere
  - No instantiation required, fully static methods
  - Centralized DBAL compatibility handling

- **AbstractCommand**: New base class for commands
  - All commands now extend from `AbstractCommand`
  - Provides common functionality and helper methods
  - Maintains backward compatibility

- **Testing Infrastructure**: New testing tools
  - Automated testing script: `scripts/test-commands.sh`
  - Comprehensive testing guide: `docs/TESTING_COMMANDS.md`

#### What's Changed

- **Code Refactoring**: Improved code organization
  - `quoteIdentifier()` logic moved to `DbalHelper` static class
  - Commands use `self::SUCCESS/FAILURE` instead of `Command::SUCCESS/FAILURE`
  - Better code reusability and maintainability

#### What's Fixed

- **PreFlightCheckService**: Fixed method call error
  - Resolved "Call to undefined method getEntityManager()" error
  - Improved database column existence checking

- **DBAL Compatibility**: Enhanced compatibility
  - Better support for different DBAL versions
  - Automatic fallback for older DBAL versions

#### Migration Steps

1. **Update the bundle**:
   ```bash
   composer update nowo-tech/anonymize-bundle
   ```

2. **Clear cache**:
   ```bash
   php bin/console cache:clear
   ```

3. **No configuration changes required**: This is a refactoring and bugfix release

#### Notes

- This release is fully backward compatible
- All existing functionality remains unchanged
- Code improvements are internal and transparent to users
- New `DbalHelper` class can be used in custom code if needed

### Upgrading to 0.0.19

**Release Date**: 2026-01-20

#### What's New

- **Demo Improvements**: Enhanced demo applications with better navigation and user experience
  - Added "Back to List" links in all form pages for better navigation
  - Added missing links in home page for EmailSubscription, SystemLog, and all MongoDB collections
  - Added SQLite connection links in sidebar navigation for all ORM entities
  - Improved consistency across all three demo applications (Symfony 6, 7, and 8)

#### What's Fixed

- **Demo Navigation**: Fixed missing `connection` parameter in home page links
  - EmailSubscription and SystemLog links now correctly include the `connection` parameter
  - Resolved routing error: "Some mandatory parameters are missing ("connection") to generate a URL"

#### Migration Steps

1. **Update the bundle**:
   ```bash
   composer update nowo-tech/anonymize-bundle
   ```

2. **Clear cache**:
   ```bash
   php bin/console cache:clear
   ```

3. **No configuration changes required**: This release only includes demo improvements and fixes

#### Notes

- This release is fully backward compatible
- All changes are in demo applications only, no bundle code changes
- No action required for production applications

### Upgrading to 0.0.18

**Release Date**: 2026-01-20

#### What's Fixed

- **Symfony 6.1+ Compatibility**: All commands now fully compatible with Symfony 6.1+, 7.0, and 8.0
  - Command help text moved from `#[AsCommand]` attribute to `setHelp()` method
  - No breaking changes, only internal improvements for compatibility

- **DatabaseExportService**: Fixed autowiring configuration issue
  - Service now correctly configured with all required parameters
  - No action required, fix is automatic

- **Test Improvements**: Fixed flaky tests and PHP warnings
  - Improved test robustness for Faker randomness
  - Fixed overflow warnings in UsernameFaker

#### Migration Steps

1. **Update the bundle**:
   ```bash
   composer update nowo-tech/anonymize-bundle
   ```

2. **Clear cache**:
   ```bash
   php bin/console cache:clear
   ```

3. **No configuration changes required**: This is a bugfix release with no breaking changes

#### Notes

- This release is fully backward compatible
- All existing functionality remains unchanged
- Improved compatibility with Symfony 6.1+

### Upgrading to 0.0.17

**Release Date**: 2026-01-20

#### What's New

- **Enhanced Reporting**: Improved statistics and export capabilities
  - New `--stats-csv` option to export statistics to CSV format
  - Success rate calculation and display (global and per-entity)
  - Enhanced statistics tables with success rate column
  - Configurable output directory for statistics via `stats_output_dir` configuration
  - Relative file paths in `--stats-json` and `--stats-csv` automatically use configured output directory
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

- **Anonymization History**: Track and manage anonymization runs
  - New `nowo:anonymize:history` command to view and manage anonymization history
  - Automatic saving of anonymization run metadata after each execution
  - List all anonymization runs with filtering options (limit, connection)
  - View detailed information about specific runs
  - Compare two anonymization runs side-by-side
  - Cleanup old runs to manage storage
  - History stored in JSON format with index file for quick access
  - Configurable history directory via `history_dir` configuration option

- **Configuration Enhancements**:
  - New `stats_output_dir` configuration option (default: `%kernel.project_dir%/var/stats`)
  - New `history_dir` configuration option (default: `%kernel.project_dir%/var/anonymize_history`)
  - New `export` configuration section for database export settings
  - All command help text moved to `#[AsCommand]` attributes

#### Breaking Changes

None - This is a backward-compatible feature release.

#### Migration Steps

1. **Update the bundle**:
   ```bash
   composer update nowo-tech/anonymize-bundle
   ```

2. **Clear cache**:
   ```bash
   php bin/console cache:clear
   ```

3. **Optional: Update configuration** (if you want to customize statistics, history, or export settings):
   ```yaml
   # config/packages/dev/nowo_anonymize.yaml
   nowo_anonymize:
       # Directory for statistics exports (JSON/CSV)
       stats_output_dir: '%kernel.project_dir%/var/stats'
       
       # Directory for anonymization history
       history_dir: '%kernel.project_dir%/var/anonymize_history'
       
       # Database export configuration
       export:
           enabled: false  # Set to true to enable
           output_dir: '%kernel.project_dir%/var/exports'
           filename_pattern: '{connection}_{database}_{date}_{time}.{format}'
           compression: gzip
           connections: []
           auto_gitignore: true
   ```

4. **Optional: Use new features**:
   ```bash
   # Export statistics to CSV
   php bin/console nowo:anonymize:run --stats-csv stats.csv
   
   # View anonymization history
   php bin/console nowo:anonymize:history
   
   # View details of a specific run
   php bin/console nowo:anonymize:history --run-id abc123def456
   
   # Compare two runs
   php bin/console nowo:anonymize:history --compare abc123,def456
   
   # Export databases
   php bin/console nowo:anonymize:export-db
   
   # Export with custom settings
   php bin/console nowo:anonymize:export-db --compression zip --output-dir /tmp/exports
   ```

#### Migration Notes

- The new configuration options are optional - defaults work out of the box
- If you don't specify `stats_output_dir`, statistics will be saved in `var/stats/` by default
- If you don't specify `history_dir`, history will be saved in `var/anonymize_history/` by default
- Anonymization history is automatically saved after each run - no action required
- Database export is disabled by default (`export.enabled: false`) - enable it in config if needed
- No database schema changes required
- Existing anonymization functionality remains unchanged

---

### Upgrading to 0.0.16

**Release Date**: 2026-01-20

#### What's New

- **Relationship Patterns Support**: You can now use patterns that reference related entities
  - Use dot notation to access related entity fields (e.g., `'type.name'`, `'customer.status'`)
  - The bundle automatically builds SQL JOINs to access related entity data
  - Works with all pattern operators: comparison, SQL LIKE, and OR operator
  - Example: `#[Anonymize(includePatterns: ['type.name' => '%HR', 'status' => 'completed'])]`
  - Only anonymizes records where the related entity's field matches the pattern

- **Demo Enhancements**: New Type entity example demonstrating relationship patterns
  - Type entity with relationship to Order
  - Comprehensive fixtures showing relationship pattern usage
  - MongoDB CRUD now visible in navigation menu

#### Breaking Changes

None - This is a backward-compatible feature release.

#### Migration Steps

1. **Update the bundle**:
   ```bash
   composer update nowo-tech/anonymize-bundle
   ```

2. **Clear cache**:
   ```bash
   php bin/console cache:clear
   ```

3. **Optional: Use relationship patterns** (if you have related entities):
   ```php
   #[Anonymize(includePatterns: ['type.name' => '%HR'])]
   class Order
   {
       #[ORM\ManyToOne]
       private ?Type $type = null;
   }
   ```

#### Migration Notes

- Relationship patterns are optional - existing patterns continue to work
- No database schema changes required
- No configuration changes required
- Relationship patterns require that the association exists in Doctrine metadata

---

### Upgrading to 0.0.15

**Release Date**: 2026-01-20

#### What's New

- **MongoDB Field Migration Command**: New command `nowo:anonymize:generate-mongo-field` to generate JavaScript scripts for adding `anonymized` field to MongoDB documents
  - Supports automatic detection of MongoDB document classes with `--scan-documents`
  - Supports manual collection specification with `--collection` option
  - Generates mongosh-compatible scripts
  - Complements the existing SQL migration command

- **Demo Enhancements**: Improved CRUD navigation in all demo projects
  - All entity CRUDs now accessible from home page
  - Better organization of links by entity type
  - Enhanced MongoDB fixture scripts

#### Breaking Changes

None - This is a backward-compatible feature release.

#### Migration Steps

1. **Update the bundle**:
   ```bash
   composer update nowo-tech/anonymize-bundle
   ```

2. **Clear cache**:
   ```bash
   php bin/console cache:clear
   ```

3. **Optional: Generate MongoDB scripts** (if using MongoDB):
   ```bash
   php bin/console nowo:anonymize:generate-mongo-field --scan-documents
   ```

#### Migration Notes

- The new MongoDB command is optional and only needed if you're preparing MongoDB documents for future anonymization
- No database schema changes required
- No configuration changes required

---

### Upgrading to 0.0.14

**Release Date**: 2026-01-20

#### What's New

- **Pattern Matching Enhancement**: PatternMatcher now supports multiple values with `|` (OR) operator
  - You can now use patterns like `'status' => 'inactive|unsubscribed'` to match multiple values
  - Supports SQL LIKE patterns with `%` wildcard: `'email' => '%@test-domain.com|%@example.com'`
  - Useful for complex pattern matching scenarios

- **Entity-Level Pattern Filtering Fix**: Entity-level patterns are now correctly applied
  - Patterns from `#[Anonymize]` attribute are now applied before processing records
  - Ensures correct filtering based on entity-level `includePatterns`/`excludePatterns`

- **Demo Enhancements**: New `EmailSubscription` entity with comprehensive pattern examples
  - Demonstrates domain-based email anonymization
  - Shows conditional anonymization based on status
  - Includes ~50 fixture records covering all pattern combinations

#### Breaking Changes

None - This is a backward-compatible bug fix and feature release.

#### Migration Steps

1. **Update the bundle**:
   ```bash
   composer update nowo-tech/anonymize-bundle
   ```

2. **Clear cache**:
   ```bash
   php bin/console cache:clear
   ```

3. **Verify installation**:
   ```bash
   php bin/console list nowo:anonymize
   ```

4. **Update your entities** (optional):
   - You can now use the `|` (OR) operator in patterns for multiple value matching
   - Example: `includePatterns: ['status' => 'inactive|unsubscribed']`
   - Example: `includePatterns: ['email' => '%@test-domain.com|%@example.com']`

#### Notes

- No configuration changes required
- Entity-level patterns now work correctly (if you were experiencing issues with filtering)
- New pattern matching features are backward compatible
- Existing anonymization configurations continue to work unchanged

### Upgrading to 0.0.13

**Release Date**: 2026-01-19

#### What's New

- **Enhanced Existing Fakers**: Improved IbanFaker, AgeFaker, NameFaker, and SurnameFaker
- **New Fakers**: Added 3 new faker types (HashPreserveFaker, ShuffleFaker, ConstantFaker)
- **Pre-flight Checks**: Comprehensive validation before anonymization execution
- **Progress Bars**: Visual progress indicators for anonymization process
- **Enhanced Environment Protection**: Improved safety checks
- **Debug and Verbose Modes**: Enhanced output options
- **Info Command**: New command to display anonymizer information
- **Event System**: Symfony events for extensibility
- **Demo Coverage**: Complete faker examples in all demos (100% coverage)

#### Breaking Changes

None - This is a backward-compatible feature release.

#### Migration Steps

1. **Update the bundle**:
   ```bash
   composer update nowo-tech/anonymize-bundle
   ```

2. **Clear cache**:
   ```bash
   php bin/console cache:clear
   ```

3. **Verify installation**:
   ```bash
   php bin/console list nowo:anonymize
   ```

#### Notes

- No configuration changes required
- New features are automatically available after update
- Existing anonymization configurations continue to work unchanged

### Upgrading to 0.0.12

**Release Date**: 2026-01-19

#### What's New

- **New Fakers**: Added 9 new faker types (Phase 1 continued implementation)
  - `password`: Generate anonymized passwords with length, special chars, numbers, and uppercase options
  - `ip_address`: Generate anonymized IP addresses (IPv4/IPv6) with version and type (public/private/localhost) options
  - `mac_address`: Generate anonymized MAC addresses with separator and uppercase options
  - `uuid`: Generate anonymized UUIDs (v1/v4) with version and format options
  - `hash`: Generate anonymized hash values (MD5, SHA1, SHA256, SHA512) with algorithm and length options
  - `coordinate`: Generate anonymized GPS coordinates with format, precision, and bounds options
  - `color`: Generate anonymized color values (hex, rgb, rgba) with format and alpha options
  - `boolean`: Generate anonymized boolean values with true_probability option
  - `numeric`: Generate anonymized numeric values (int/float) with type, min, max, and precision options
  - All new fakers registered in `FakerType` enum and `FakerFactory`
  - Total fakers available: 23 (8 original + 15 new)

- **Testing Improvements**:
  - Comprehensive test coverage: 216 tests, 512 assertions
  - Code coverage: 45.80% line coverage (414/904 lines), 52.78% class coverage (19/36 classes)
  - All fakers have dedicated test suites

- **Service Registration Fix**:
  - Fixed `MaskingFaker` service registration issue
  - Explicit service definition added to `services.yaml`

#### Breaking Changes

None - This is a backward-compatible feature release.

#### Migration Steps

1. **Update the bundle**:
   ```bash
   composer update nowo-tech/anonymize-bundle
   ```

2. **Clear cache**:
   ```bash
   php bin/console cache:clear
   ```

3. **Verify installation**:
   ```bash
   php bin/console list nowo:anonymize
   ```

4. **Update your entities** (optional):
   - You can now use the new faker types in your `#[AnonymizeProperty]` attributes
   - See [README.md](../README.md) for examples of each faker type

#### Notes

- No configuration changes required
- New fakers are automatically available after update
- Existing anonymization configurations continue to work unchanged
- You can start using new faker types immediately in your entities
- See [ROADMAP.md](ROADMAP.md) for planned future fakers

### Upgrading to 0.0.11

**Release Date**: TBD

#### What's New

- **New Fakers**: Added 6 new faker types (Phase 1 implementation)
  - `address`: Generate anonymized street addresses with country, format, and postal code options
  - `date`: Generate anonymized dates with min/max date, format, and type (past/future/between) options
  - `username`: Generate anonymized usernames with length, prefix, suffix, and number options
  - `url`: Generate anonymized URLs with scheme, domain, and path options
  - `company`: Generate anonymized company names with type (corporation/llc/inc) and suffix options
  - `masking`: Partial masking of sensitive data with preserve_start, preserve_end, mask_char options
  - All new fakers are registered in `FakerType` enum and `FakerFactory`
  - Total fakers available: 14 (8 original + 6 new)

- **Enhanced Demos**: Added 4 new entities with complete CRUD interfaces
  - `Product` entity: Demonstrates name, url, date fakers (10 products)
    - Complete CRUD: ProductController, ProductType form, templates (index, show, new, edit)
  - `Order` entity: Demonstrates service, address, date, email fakers with patterns (13 orders)
    - Complete CRUD: OrderController, OrderType form, templates (index, show, new, edit)
  - `Invoice` entity: Demonstrates masking, company, iban, service fakers (8 invoices)
    - Complete CRUD: InvoiceController, InvoiceType form, templates (index, show, new, edit)
  - `Employee` entity: Demonstrates username, date, company fakers with exclusion patterns (12 employees)
    - Complete CRUD: EmployeeController, EmployeeType form, templates (index, show, new, edit)
  - All entities include `AnonymizableTrait` for anonymization tracking
  - Comprehensive fixtures with realistic test data
  - Updated navigation menu with all entities organized by categories
  - Added anonymization field alerts in all entity list views explaining which fields are anonymized

- **Custom Service Faker Example**: Added example service in demos
  - `CustomReferenceFaker`: Example service implementing `FakerInterface`
  - Demonstrates how to create custom anonymizers
  - Used in Customer and Order entities
  - Available in all demo projects (Symfony 6, 7, 8)

#### Breaking Changes

None - This is a backward-compatible feature release.

#### Migration Steps

1. **Update the bundle**:
   ```bash
   composer update nowo-tech/anonymize-bundle
   ```

2. **Clear cache**:
   ```bash
   php bin/console cache:clear
   ```

3. **Verify installation**:
   ```bash
   php bin/console list nowo:anonymize
   ```

4. **Update your entities** (optional):
   - You can now use the new faker types in your `#[AnonymizeProperty]` attributes
   - See [README.md](../README.md) for examples of each faker type

#### Notes

- No configuration changes required
- New fakers are automatically available after update
- Existing anonymization configurations continue to work unchanged
- You can start using new faker types immediately in your entities
- See [ROADMAP.md](ROADMAP.md) for planned future fakers

### Upgrading to 0.0.10

**Release Date**: 2026-01-19

#### What's New

- **Doctrine DBAL Compatibility**: Fixed deprecation warnings
  - Replaced deprecated `quoteIdentifier()` with `quoteSingleIdentifier()`
  - Compatible with both Doctrine DBAL 2.x and 3.x
  - No functional changes, only deprecation fixes

#### Breaking Changes

None - This is a backward-compatible bug fix release.

#### Migration Steps

1. **Update the bundle**:
   ```bash
   composer update nowo-tech/anonymize-bundle
   ```

2. **Clear cache**:
   ```bash
   php bin/console cache:clear
   ```

3. **Verify installation**:
   ```bash
   php bin/console list nowo:anonymize
   ```

#### Notes

- No configuration changes required
- This fix resolves deprecation warnings in Doctrine DBAL 3.x
- All functionality remains the same

### Upgrading to 0.0.9

**Release Date**: 2026-01-19

#### What's New

- **Bug Fixes**: Fixed critical issues in AnonymizeService
  - Fixed `quote()` method to handle integer values correctly
  - Resolves errors when anonymizing entities with integer IDs
  - Improved database compatibility

- **Demo Improvements**: Enhanced demo project
  - Expanded fixtures with more test data
  - Better PostgreSQL compatibility
  - More comprehensive examples

#### Breaking Changes

None - This is a backward-compatible bug fix release.

#### Migration Steps

1. **Update the bundle**:
   ```bash
   composer update nowo-tech/anonymize-bundle
   ```

2. **Clear cache**:
   ```bash
   php bin/console cache:clear
   ```

3. **Verify installation**:
   ```bash
   php bin/console list nowo:anonymize
   ```

#### Notes

- No configuration changes required
- If you encounter errors with integer IDs, this fix resolves them
- Demo projects have been updated with more comprehensive test data

### Upgrading to 0.0.8

**Release Date**: 2026-01-19

#### What's New

- **GitHub Release Workflow**: Fixed release creation to mark as latest
  - Added `make_latest: !isPrerelease` parameter to `createRelease` call
  - New releases are now automatically marked as latest
  - Ensures consistency between release creation and update workflows

- **Documentation**: Enhanced upgrade guide
  - Added complete upgrade instructions for v0.0.7
  - Updated compatibility table with all versions
  - Improved documentation consistency

#### Breaking Changes

None - This is a backward-compatible update.

#### Migration Steps

1. **Update the bundle**:
   ```bash
   composer update nowo-tech/anonymize-bundle
   ```

2. **Clear cache**:
   ```bash
   php bin/console cache:clear
   ```

#### Notes

- No configuration changes required
- This is primarily a workflow and documentation update

### Upgrading to 0.0.7

**Release Date**: 2026-01-19

#### What's New

- **SchemaService**: New service for checking database schema information
  - `hasAnonymizedColumn()` method to check if anonymized column exists
  - `hasColumn()` generic method to check any column existence
  - Service is autowired and available for dependency injection
  - Comprehensive test coverage with 8 test cases

- **Code Improvements**: Refactored demo controllers to use SchemaService
  - Removed duplicate code from demo controllers
  - Improved code organization and reusability
  - Better separation of concerns

- **Documentation**: Enhanced demo documentation
  - All demo texts translated to English
  - Complete documentation of anonymized column feature
  - Better examples and instructions

#### Breaking Changes

None - This is a backward-compatible update.

#### Migration Steps

1. **Update the bundle**:
   ```bash
   composer update nowo-tech/anonymize-bundle
   ```

2. **Clear cache**:
   ```bash
   php bin/console cache:clear
   ```

3. **Verify installation**:
   ```bash
   php bin/console list nowo:anonymize
   ```

#### Notes

- SchemaService is automatically available via dependency injection
- No configuration changes required
- If you have custom code checking for `anonymized` column, consider using SchemaService instead

### Upgrading to 0.0.6

**Release Date**: 2026-01-19

#### What's New

- **Symfony 8 Compatibility**: Full support for Symfony 8.0
  - Updated `doctrine/doctrine-bundle` constraint to support both 2.x and 3.x
  - Symfony 8 requires doctrine-bundle 3.x, while Symfony 6/7 use 2.x
  - Bundle now compatible with all Symfony versions (6.1+, 7.0, 8.0)

#### Breaking Changes

None - This is a backward-compatible update.

#### Migration Steps

1. **Update the bundle**:
   ```bash
   composer update nowo-tech/anonymize-bundle
   ```

2. **Clear cache**:
   ```bash
   php bin/console cache:clear
   ```

3. **Verify installation**:
   ```bash
   php bin/console list nowo:anonymize
   ```

#### Notes

- If you're using Symfony 8, ensure you have `doctrine/doctrine-bundle` ^3.0 installed
- Symfony Flex will automatically resolve the correct version
- No configuration changes required

### Upgrading to 0.0.5

**Release Date**: 2026-01-19

#### What's New

- **Doctrine Bundle Compatibility**: Improved compatibility with Symfony 8
  - Updated `doctrine/doctrine-bundle` constraint from `^2.15` to `^2.8`
  - Allows broader compatibility across Symfony 6, 7, and 8
  - Symfony Flex can now resolve compatible versions automatically

#### Breaking Changes

None - This is a backward-compatible update.

#### Migration Steps

1. **Update the bundle**:
   ```bash
   composer update nowo-tech/anonymize-bundle
   ```

2. **Clear cache**:
   ```bash
   php bin/console cache:clear
   ```

### Upgrading to 0.0.4

**Release Date**: 2026-01-19

#### What's New

- **Anonymized Column Tracking**: New feature to track which records have been anonymized
  - `AnonymizableTrait`: Trait to add `anonymized` boolean field to entities
  - `nowo:anonymize:generate-column-migration` command: Generates SQL migrations
  - Automatic flag setting: Records are automatically marked as anonymized

#### Breaking Changes

None - This is a backward-compatible feature addition.

#### Migration Steps

1. **Update the bundle**:
   ```bash
   composer update nowo-tech/anonymize-bundle
   ```

2. **Add AnonymizableTrait to your entities** (optional):
   ```php
   use Nowo\AnonymizeBundle\Trait\AnonymizableTrait;

   #[ORM\Entity]
   #[Anonymize]
   class User
   {
       use AnonymizableTrait;
       // ... rest of your entity
   }
   ```

3. **Generate migration for the `anonymized` column**:
   ```bash
   php bin/console nowo:anonymize:generate-column-migration
   ```

4. **Apply the generated SQL migration** to your database(s)

5. **Clear cache**:
   ```bash
   php bin/console cache:clear
   ```

6. **Test anonymization** - Records will now be automatically marked:
   ```bash
   php bin/console nowo:anonymize:run --dry-run
   ```

#### New Command: `nowo:anonymize:generate-column-migration`

This command scans all entities using `AnonymizableTrait` and generates SQL migrations to add the `anonymized` column.

**Usage**:
```bash
# Generate migrations for all connections
php bin/console nowo:anonymize:generate-column-migration

# Generate migrations for specific connections
php bin/console nowo:anonymize:generate-column-migration --connection default

# Output SQL to a file
php bin/console nowo:anonymize:generate-column-migration --output migrations/add_anonymized_column.sql
```

**Options**:
- `--connection, -c`: Process only specific connections (can be used multiple times)
- `--output, -o`: Output SQL to a file instead of console

#### Using AnonymizableTrait

The `AnonymizableTrait` provides:
- An `anonymized` boolean field (default: `false`)
- `isAnonymized()`: Check if a record has been anonymized
- `setAnonymized(bool)`: Manually set anonymization status

**Example**:
```php
use Nowo\AnonymizeBundle\Trait\AnonymizableTrait;

#[ORM\Entity]
#[Anonymize]
class User
{
    use AnonymizableTrait;
    
    // ... your properties
}

// After anonymization, check status:
$user = $entityManager->find(User::class, 1);
if ($user->isAnonymized()) {
    // This record has been anonymized
}
```

**Query anonymized records**:
```sql
SELECT * FROM users WHERE anonymized = true;
```

#### Migration Workflow

1. Add `AnonymizableTrait` to your entities
2. Run `nowo:anonymize:generate-column-migration` to generate SQL
3. Review and apply the generated SQL to your database(s)
4. Run anonymization - records will be automatically marked

### Installing the bundle (first-time install)

This section applies when installing the bundle for the first time (no prior version).

#### What's New

- **Initial release**: Complete database anonymization functionality for Symfony
  - Attribute-based configuration system
  - Support for multiple Doctrine connections
  - Multiple faker types with customization options
  - Weight-based processing order
  - Pattern matching for selective anonymization
  - Comprehensive statistics and reporting
  - Dry-run mode for safe testing

#### Breaking Changes

N/A - This is the initial release.

#### Migration Steps

1. **Install the bundle**:
   ```bash
   composer require nowo-tech/anonymize-bundle
   ```

2. **Register the bundle** in `config/bundles.php` **only for dev and test environments**:
   ```php
   return [
       // ...
       Nowo\AnonymizeBundle\AnonymizeBundle::class => ['dev' => true, 'test' => true],
   ];
   ```

3. **Configure the bundle** (optional - uses defaults if not configured):
   ```yaml
   # config/packages/dev/nowo_anonymize.yaml
   nowo_anonymize:
       locale: 'en_US'
       connections: []
       dry_run: false
       batch_size: 100
   ```

4. **Mark entities for anonymization**:
   ```php
   use Nowo\AnonymizeBundle\Attribute\Anonymize;
   use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;

   #[Anonymize]
   class User
   {
       #[AnonymizeProperty(type: 'email', weight: 1)]
       private string $email;

       #[AnonymizeProperty(type: 'name', weight: 2)]
       private string $firstName;
   }
   ```

5. **Clear cache**:
   ```bash
   php bin/console cache:clear
   ```

6. **Test anonymization** (dry-run mode):
   ```bash
   php bin/console nowo:anonymize:run --dry-run
   ```

7. **Run anonymization**:
   ```bash
   php bin/console nowo:anonymize:run
   ```

#### Configuration Options

The bundle supports the following configuration options:

```yaml
nowo_anonymize:
    # Default locale for Faker generators
    locale: 'en_US'
    
    # Specific connections to process (empty = all connections)
    connections: []
    
    # Default dry-run mode (can be overridden via command option)
    dry_run: false
    
    # Batch size for processing records
    batch_size: 100
```

#### Faker Types

The bundle supports the following faker types:

- `email` - Email addresses
- `name` - First names
- `surname` - Last names
- `age` - Ages (with min/max options)
- `phone` - Phone numbers
- `iban` - IBAN codes (with country option)
- `credit_card` - Credit card numbers
- `service` - Custom service (requires service name)

#### Example Usage

**Basic Entity Configuration**:
```php
#[Anonymize]
class User
{
    #[AnonymizeProperty(type: 'email', weight: 1)]
    private string $email;

    #[AnonymizeProperty(type: 'name', weight: 2)]
    private string $firstName;

    #[AnonymizeProperty(type: 'surname', weight: 3)]
    private string $lastName;
}
```

**With Pattern Matching**:
```php
#[Anonymize]
class Customer
{
    #[AnonymizeProperty(
        type: 'email',
        weight: 1,
        includePatterns: ['status' => 'active'],
        excludePatterns: ['id' => '1']
    )]
    private string $email;
}
```

**With Custom Options**:
```php
#[AnonymizeProperty(
    type: 'age',
    weight: 4,
    options: ['min' => 18, 'max' => 100]
)]
private int $age;

#[AnonymizeProperty(
    type: 'iban',
    weight: 6,
    options: ['country' => 'ES']
)]
private string $iban;
```

**With Custom Service**:
```php
#[AnonymizeProperty(
    type: 'service',
    weight: 8,
    service: 'app.custom_anonymizer'
)]
private string $customField;
```

## Troubleshooting Upgrades

### Common Issues

#### Issue: "Unrecognized option" error after upgrade

**Solution**: Clear Symfony cache and update composer dependencies:
```bash
php bin/console cache:clear
composer update nowo-tech/anonymize-bundle
```

#### Issue: Configuration validation errors

**Solution**: Check your configuration against the latest documentation:
```bash
php bin/console debug:config nowo_anonymize
```

#### Issue: Services not found after upgrade

**Solution**: Clear cache and rebuild container:
```bash
php bin/console cache:clear
php bin/console cache:warmup
```

#### Issue: Entities not being anonymized

**Solution**: 
1. Verify entities have the `#[Anonymize]` attribute
2. Verify properties have the `#[AnonymizeProperty]` attribute
3. Check that entities are mapped in Doctrine:
   ```bash
   php bin/console doctrine:mapping:info
   ```
4. Run in dry-run mode to see what would be anonymized:
   ```bash
   php bin/console nowo:anonymize:run --dry-run
   ```

#### Issue: Pattern matching not working

**Solution**:
1. Verify pattern syntax matches your database column names
2. Check that patterns use the correct data types (strings, numbers)
3. Test patterns in dry-run mode first
4. Review the PatternMatcher documentation

#### Issue: Custom service faker not working

**Solution**:
1. Verify the service exists and is public:
   ```bash
   php bin/console debug:container app.custom_anonymizer
   ```
2. Ensure the service implements `FakerInterface` or has a `generate()` method
3. Check service configuration in `config/services.yaml`

### Getting Help

If you encounter issues during upgrade:

1. Check the [CHANGELOG.md](CHANGELOG.md) for known issues
2. Review the [CONFIGURATION.md](CONFIGURATION.md) for configuration examples
3. Review the [INSTALLATION.md](INSTALLATION.md) for installation instructions
4. Open an issue on [GitHub](https://github.com/nowo-tech/anonymize-bundle/issues)

## Version Compatibility

| Bundle Version | Symfony Version | PHP Version | Doctrine Bundle | Features |
|---------------|-----------------|-------------|-----------------|----------|
| 1.0.10+       | 6.1+, 7.0, 8.0  | 8.1, 8.2, 8.3, 8.4, 8.5 | ^2.8 \|\| ^3.0 | anonymizeService-only entities (no AnonymizeProperty required) |
| 1.0.9         | 6.1+, 7.0, 8.0  | 8.1, 8.2, 8.3, 8.4, 8.5 | ^2.8 \|\| ^3.0 | FakerFactory/PreFlightCheckService explicit DI, no synthetic kernel usage, --entity without -e, demo Makefiles aligned |
| 1.0.8         | 6.1+, 7.0, 8.0  | 8.1, 8.2, 8.3, 8.4, 8.5 | ^2.8 \|\| ^3.0 | --entity option for nowo:anonymize:run, FakerFactory alias and doc for app services |
| 1.0.7         | 6.1+, 7.0, 8.0  | 8.1, 8.2, 8.3, 8.4, 8.5 | ^2.8 \|\| ^3.0 | DBAL 4–compatible identifier quoting via platform, CI tests on PHP 8.1 |
| 1.0.6         | 6.1+, 7.0, 8.0  | 8.1, 8.2, 8.3, 8.4, 8.5 | ^2.8 \|\| ^3.0 | PostgreSQL boolean TRUE/FALSE for `anonymized` column, ROADMAP adoption strategy |
| 1.0.5         | 6.1+, 7.0, 8.0  | 8.1, 8.2, 8.3, 8.4, 8.5 | ^2.8 \|\| ^3.0 | anonymizeService, truncate by discriminator, Doctrine ORM 3 discriminatorColumn, UtmFaker campaign min_length, demo notification breadcrumb fix |
| 1.0.4         | 6.1+, 7.0, 8.0  | 8.1, 8.2, 8.3, 8.4, 8.5 | ^2.8 \|\| ^3.0 | Map faker, demo AnonymizePropertySubscriber, FakerFactory FakerType keys, UtmFaker term min_length fix |
| 0.0.25+       | 6.1+, 7.0, 8.0  | 8.1, 8.2, 8.3, 8.4, 8.5 | ^2.8 \|\| ^3.0 | Fixed FakerFactory autowiring error |
| 0.0.24        | 6.1+, 7.0, 8.0  | 8.1, 8.2, 8.3, 8.4, 8.5 | ^2.8 \|\| ^3.0 | Simplified services.yaml, complete documentation update |
| 0.0.23        | 6.1+, 7.0, 8.0  | 8.1, 8.2, 8.3, 8.4, 8.5 | ^2.8 \|\| ^3.0 | Standardized faker API with original_value, #[Autowire] attributes |
| 0.0.10-0.0.22 | 6.0, 7.0, 8.0   | 8.1, 8.2, 8.3, 8.4, 8.5 | ^2.8 \|\| ^3.0 | Doctrine DBAL 3.x compatibility, Deprecation fixes |
| 0.0.9         | 6.0, 7.0, 8.0   | 8.1, 8.2, 8.3, 8.4, 8.5 | ^2.8 \|\| ^3.0 | Bug fixes, Improved demo, PostgreSQL compatibility |
| 0.0.8         | 6.0, 7.0, 8.0   | 8.1, 8.2, 8.3, 8.4, 8.5 | ^2.8 \|\| ^3.0 | Release workflow fix, Documentation improvements |
| 0.0.7         | 6.0, 7.0, 8.0   | 8.1, 8.2, 8.3, 8.4, 8.5 | ^2.8 \|\| ^3.0 | SchemaService, Improved code organization, Enhanced documentation |
| 0.0.6         | 6.0, 7.0, 8.0   | 8.1, 8.2, 8.3, 8.4, 8.5 | ^2.8 \|\| ^3.0 | Full Symfony 8 support, Anonymized column tracking, Multiple faker types, Pattern matching, Statistics |
| 0.0.5         | 6.0, 7.0, 8.0   | 8.1, 8.2, 8.3, 8.4, 8.5 | ^2.8 | Improved Symfony 8 compatibility |
| 0.0.4         | 6.0, 7.0, 8.0   | 8.1, 8.2, 8.3, 8.4, 8.5 | ^2.8 | Anonymized column tracking |
| 0.0.3         | 6.0, 7.0        | 8.1, 8.2, 8.3, 8.4, 8.5 | ^2.8 | Comprehensive tests, Faker services, PSR-11 |
| 0.0.2         | 6.0, 7.0        | 8.1, 8.2, 8.3, 8.4, 8.5 | ^2.8 | Autowiring fixes |
| 0.0.1         | 6.0, 7.0        | 8.1, 8.2, 8.3, 8.4, 8.5 | ^2.8 | Initial release |

## Additional Resources

- [CHANGELOG.md](CHANGELOG.md) - Complete version history
- [CONFIGURATION.md](CONFIGURATION.md) - Detailed configuration guide
- [INSTALLATION.md](INSTALLATION.md) - Installation instructions
- [README.md](../README.md) - Main documentation

## Notes

- Always test anonymization in dry-run mode first
- Always backup your database before running anonymization
- Review breaking changes in the changelog before upgrading
- The bundle maintains backward compatibility within major versions (1.x.x)
- Pattern matching is case-sensitive and must match database column names exactly
- Weight values determine processing order (lower weights are processed first)
- Properties without weights are processed last, in alphabetical order
