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

## Available Faker Types

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

- **address**: Generates anonymized street addresses
  - Options: `country` (string), `include_postal_code` (bool), `format` ('full'/'short')
- **date**: Generates anonymized dates
  - Options: `min_date`, `max_date`, `format`, `type` ('past'/'future'/'between')
- **username**: Generates anonymized usernames
  - Options: `min_length`, `max_length`, `prefix`, `suffix`, `include_numbers`
- **url**: Generates anonymized URLs
  - Options: `scheme` ('http'/'https'), `domain`, `path` (bool)
- **company**: Generates anonymized company names
  - Options: `type` ('corporation'/'llc'/'inc'), `suffix` (string)
- **masking**: Partial masking of sensitive data
  - Options: `preserve_start` (int), `preserve_end` (int), `mask_char` (string), `mask_length` (int)
  - Requires `value` option with the original value to mask
- **password**: Generates anonymized passwords
  - Options: `length` (int), `include_special` (bool), `include_numbers` (bool), `include_uppercase` (bool)
- **ip_address**: Generates anonymized IP addresses
  - Options: `version` (int, 4 or 6), `type` (string, 'public'/'private'/'localhost')
- **mac_address**: Generates anonymized MAC addresses
  - Options: `separator` (string, 'colon'/'dash'/'none'), `uppercase` (bool)
- **uuid**: Generates anonymized UUIDs
  - Options: `version` (int, 1 or 4), `format` (string, 'with_dashes'/'without_dashes')
- **hash**: Generates anonymized hash values
  - Options: `algorithm` (string, 'md5'/'sha1'/'sha256'/'sha512'), `length` (int|null)
- **coordinate**: Generates anonymized GPS coordinates
  - Options: `format` (string, 'array'/'string'/'json'), `precision` (int), `min_lat`, `max_lat`, `min_lng`, `max_lng` (float)
- **color**: Generates anonymized color values
  - Options: `format` (string, 'hex'/'rgb'/'rgba'), `alpha` (float, 0.0-1.0)
- **boolean**: Generates anonymized boolean values
  - Options: `true_probability` (int, 0-100)
- **numeric**: Generates anonymized numeric values
  - Options: `type` (string, 'int'/'float'), `min`, `max` (int|float), `precision` (int, for floats)

### Custom Fakers

- **service**: Uses a custom service for anonymization
  - Requires `service` option with the service name
  - Service must implement `FakerInterface` or have a `generate()` method
