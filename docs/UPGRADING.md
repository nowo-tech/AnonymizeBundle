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

#### Upgrade Steps

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

#### Upgrade Steps

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

#### Upgrade Steps

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

#### Upgrade Steps

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

#### Upgrade Steps

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

#### Upgrade Steps

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

#### Upgrade Steps

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

#### Upgrade Steps

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

#### Upgrade Steps

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

#### Upgrade Steps

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
  - Bundle now compatible with all Symfony versions (6.0, 7.0, 8.0)

#### Breaking Changes

None - This is a backward-compatible update.

#### Upgrade Steps

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

#### Upgrade Steps

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

#### Upgrade Steps

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

### Upgrading to 1.0.0 (Initial Release)

**Release Date**: TBD

#### What's New

- **Initial Release**: Complete database anonymization functionality for Symfony
  - Attribute-based configuration system
  - Support for multiple Doctrine connections
  - Multiple faker types with customization options
  - Weight-based processing order
  - Pattern matching for selective anonymization
  - Comprehensive statistics and reporting
  - Dry-run mode for safe testing

#### Breaking Changes

N/A - This is the initial release.

#### Upgrade Steps

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
| 0.0.10        | 6.0, 7.0, 8.0   | 8.1, 8.2, 8.3, 8.4, 8.5 | ^2.8 \|\| ^3.0 | Doctrine DBAL 3.x compatibility, Deprecation fixes |
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
