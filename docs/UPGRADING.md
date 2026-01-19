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
