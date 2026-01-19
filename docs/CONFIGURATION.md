# Configuration Guide

> ⚠️ **Important**: This bundle is **development-only** and should **never** be installed or used in production environments. The command includes built-in protection that prevents execution outside of dev/test environments.

## Configuration File

> 📍 **Location**: The configuration file is automatically created at `config/packages/dev/nowo_anonymize.yaml` when using Symfony Flex, since this bundle is development-only.

The bundle configuration is defined in `config/packages/dev/nowo_anonymize.yaml`:

```yaml
nowo_anonymize:
    locale: 'en_US'              # Locale for Faker generator
    connections: []              # Specific connections to process (empty = all)
    dry_run: false              # Dry-run mode (default: false)
    batch_size: 100             # Batch size for processing records
```

## Configuration Options

### locale

**Type**: `string`  
**Default**: `'en_US'`  
**Description**: Locale for the Faker generator. This determines the language and format of generated anonymized data.

**Examples**:
- `'en_US'` - English (United States)
- `'es_ES'` - Spanish (Spain)
- `'fr_FR'` - French (France)
- `'de_DE'` - German (Germany)

### connections

**Type**: `array<string>`  
**Default**: `[]`  
**Description**: Array of Doctrine connection names to process. If empty, all connections will be processed.

**Example**:
```yaml
nowo_anonymize:
    connections:
        - default
        - secondary
```

### dry_run

**Type**: `boolean`  
**Default**: `false`  
**Description**: If `true`, the command will only show what would be anonymized without making any changes to the database. Useful for testing and previewing changes.

**Example**:
```yaml
nowo_anonymize:
    dry_run: true
```

### batch_size

**Type**: `integer`  
**Default**: `100`  
**Description**: Number of records to process in each batch. Larger batch sizes may improve performance but use more memory.

**Example**:
```yaml
nowo_anonymize:
    batch_size: 50
```

## Environment-Specific Configuration

> ⚠️ **Important**: This bundle should **only** be configured for `dev` and `test` environments. Never configure it for production.

You can override configuration for specific environments:

```yaml
# config/packages/dev/nowo_anonymize.yaml
nowo_anonymize:
    dry_run: true  # Always use dry-run in development

# config/packages/test/nowo_anonymize.yaml
nowo_anonymize:
    batch_size: 50  # Smaller batch size for testing
```

## Command-Line Overrides

All configuration options can be overridden via command-line options:

```bash
# Override locale
php bin/console nowo:anonymize:run --locale en_US

# Override connections
php bin/console nowo:anonymize:run --connection default

# Enable dry-run
php bin/console nowo:anonymize:run --dry-run

# Override batch size
php bin/console nowo:anonymize:run --batch-size 50
```

Command-line options take precedence over configuration file values.
