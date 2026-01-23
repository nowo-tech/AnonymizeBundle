# Configuration Guide

> ⚠️ **Important**: This bundle is **development-only** and should **never** be installed or used in production environments. The command includes built-in protection that prevents execution outside of dev/test environments.

> 📋 **Requirements**: This bundle requires **Symfony 6.1 or higher** (Symfony 6.0 is not supported). See [INSTALLATION.md](INSTALLATION.md) for complete requirements.

## Configuration File

> 📍 **Location**: The configuration file should be located at `config/packages/dev/nowo_anonymize.yaml` (since this bundle is development-only).

> ⚠️ **Important**: The configuration file is **only automatically created** when:
> - The bundle is installed from **Packagist** (not from a local repository or private repository)
> - **Symfony Flex** is enabled and can access the official Symfony recipes repository
> - The recipe is published in the Symfony recipes repository
>
> If the file was not created automatically, you need to **manually create** it.

The bundle configuration is defined in `config/packages/dev/nowo_anonymize.yaml`:

```yaml
nowo_anonymize:
    locale: 'en_US'              # Locale for Faker generator
    connections: []              # Specific connections to process (empty = all)
    dry_run: false              # Dry-run mode (default: false)
    batch_size: 100             # Batch size for processing records
    stats_output_dir: '%kernel.project_dir%/var/stats'  # Output directory for statistics (JSON/CSV)
    history_dir: '%kernel.project_dir%/var/anonymize_history'  # Directory for anonymization history
    export:                      # Database export configuration
        enabled: false           # Enable export functionality
        output_dir: '%kernel.project_dir%/var/exports'  # Output directory
        filename_pattern: '{connection}_{database}_{date}_{time}.{format}'  # Filename pattern
        compression: gzip        # Compression format: none, gzip, bzip2, zip
        connections: []          # Specific connections to export (empty = all)
        auto_gitignore: true     # Automatically update .gitignore
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

### history_dir

**Type**: `string`  
**Default**: `'%kernel.project_dir%/var/anonymize_history'`  
**Description**: Directory where anonymization history will be stored. History includes metadata and statistics for each anonymization run.

**Example**:
```yaml
nowo_anonymize:
    history_dir: '%kernel.project_dir%/var/anonymize_history'
```

**Note**: The history directory is automatically created if it doesn't exist. Each anonymization run is saved as a JSON file with metadata and statistics.

### export

**Type**: `array`  
**Default**: `disabled` (must be explicitly enabled)  
**Description**: Configuration for database export functionality.

**Sub-options**:

- **`enabled`** (boolean, default: `false`): Enable export functionality
- **`output_dir`** (string, default: `'%kernel.project_dir%/var/exports'`): Directory where exports will be saved
- **`filename_pattern`** (string, default: `'{connection}_{database}_{date}_{time}.{format}'`): Filename pattern with placeholders:
  - `{connection}` - Connection name
  - `{database}` - Database name
  - `{date}` - Current date (Y-m-d format)
  - `{time}` - Current time (H-i-s format)
  - `{format}` - File extension (sql, sqlite, bson)
- **`compression`** (string, default: `'gzip'`): Compression format. Options: `none`, `gzip`, `bzip2`, `zip`
- **`connections`** (array, default: `[]`): Specific connections to export. Empty array means all connections
- **`auto_gitignore`** (boolean, default: `true`): Automatically create/update `.gitignore` to exclude export directory

**Example**:
```yaml
nowo_anonymize:
    export:
        enabled: true
        output_dir: '%kernel.project_dir%/var/exports'
        filename_pattern: '{connection}_{database}_{date}_{time}.{format}'
        compression: gzip
        connections: []  # Export all connections
        auto_gitignore: true
```

**Filename Pattern Examples**:
```yaml
# Simple pattern
filename_pattern: '{database}_{date}.{format}'

# With connection and time
filename_pattern: '{connection}_{database}_{date}_{time}.{format}'

# Custom format
filename_pattern: 'backup_{database}_{date}.{format}'
```

**Compression Notes**:
- `gzip`: Requires `gzip` command (usually pre-installed on Linux/Mac)
- `bzip2`: Requires `bzip2` command (usually pre-installed on Linux/Mac)
- `zip`: Requires PHP `ZipArchive` extension (usually pre-installed)
- `none`: No compression applied

The export command will automatically detect available compression tools and fall back gracefully if a tool is not available.

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

# Disable progress bar
php bin/console nowo:anonymize:run --no-progress

# Enable verbose mode
php bin/console nowo:anonymize:run --verbose

# Enable debug mode
php bin/console nowo:anonymize:run --debug
```

Command-line options take precedence over configuration file values.

## Available Commands

The bundle provides six console commands. See [COMMANDS.md](COMMANDS.md) for detailed command documentation, options, and examples.

## Pattern Matching

The bundle supports **two-level pattern matching** for fine-grained control over anonymization:

### Entity-Level Patterns

Defined in the `#[Anonymize]` attribute, these patterns determine if a **record** is a candidate for anonymization.

**Syntax**:
```php
#[Anonymize(
    includePatterns: ['column' => 'pattern'],
    excludePatterns: ['column' => 'pattern'],
    truncate: false,           // Optional: empty table before anonymization
    truncate_order: null        // Optional: order for truncation (lower = earlier)
)]
```

**Behavior**:
- If patterns match → Record is a candidate (properties are evaluated)
- If patterns don't match → **Entire record is skipped** (nothing is anonymized)
- If no patterns defined → All records are candidates
- If `truncate: true` → Table is emptied **BEFORE** anonymization (executed first)
- If `truncate_order` is set → Tables are truncated in order (lower numbers = earlier)
- If `truncate_order` is null → Tables are truncated alphabetically after explicit orders

### Property-Level Patterns

Defined in the `#[AnonymizeProperty]` attribute, these patterns determine if a **property** should be anonymized.

**Syntax**:
```php
#[AnonymizeProperty(
    type: 'email',
    includePatterns: ['column' => 'pattern'],
    excludePatterns: ['column' => 'pattern']
)]
```

**Behavior**:
- Only evaluated when the record is already a candidate (entity patterns matched)
- If patterns match → Property is anonymized
- If patterns don't match → Property is skipped (left unchanged)
- If no patterns defined → Property is anonymized (when record is candidate)

### Pattern Combination Logic

The anonymization decision follows this flow:

```
1. Check entity-level patterns
   ├─ NO match → Skip entire record (nothing anonymized)
   └─ YES match → Continue to step 2

2. For each property with #[AnonymizeProperty]:
   ├─ Check property-level patterns
   │  ├─ NO match → Skip this property (leave unchanged)
   │  └─ YES match → Anonymize this property
   └─ If no patterns → Anonymize this property
```

**Key Points**:
- Entity patterns act as a **gate**: if they don't match, nothing happens
- Property patterns act as a **filter**: they determine which properties are anonymized
- Both must match (when defined) for anonymization to occur
- Exclusions take precedence over inclusions at both levels

### Pattern Operators

- **Comparison**: `>`, `>=`, `<`, `<=`, `=`, `!=`, `<>`
- **SQL LIKE**: `%` wildcard (e.g., `'%@example.com'`)
- **OR Operator**: `|` for multiple values (e.g., `'active|inactive'`)
- **Relationship Access**: Dot notation for related entities (e.g., `'type.name'`, `'customer.status'`)

### Relationship Patterns

Patterns can reference fields from related entities using dot notation:

```php
#[Anonymize(includePatterns: ['type.name' => '%HR'])]
class Order
{
    #[ORM\ManyToOne]
    private ?Type $type = null;
}
```

The bundle automatically:
- Detects relationship patterns (fields containing `.`)
- Builds SQL queries with `LEFT JOIN` clauses
- Accesses related entity fields for pattern matching

**Supported relationship types:**
- `ManyToOne` (most common)
- `OneToOne`
- `OneToMany` (via inverse side)

**Limitations:**
- Only direct relationships are supported (one level: `type.name`)
- Nested relationships (e.g., `order.customer.address.city`) may work but are not fully tested
- The association must exist in Doctrine metadata

See [USAGE.md](USAGE.md) for detailed examples and use cases.

## Event System

The bundle provides a comprehensive event system for extensibility. You can listen to events to customize the anonymization process.

### Available Events

#### BeforeAnonymizeEvent

Dispatched once before anonymization starts, before any entities are processed.

**Properties**:
- `getEntityManager()`: Returns the EntityManagerInterface
- `getEntityClasses()`: Returns array of entity class names to be anonymized
- `setEntityClasses(array $entityClasses)`: Modify which entities will be processed
- `isDryRun()`: Returns whether this is a dry run

#### AfterAnonymizeEvent

Dispatched once after anonymization completes, after all entities have been processed.

**Properties**:
- `getEntityManager()`: Returns the EntityManagerInterface
- `getEntityClasses()`: Returns array of entity class names that were anonymized
- `getTotalProcessed()`: Returns total number of records processed
- `getTotalUpdated()`: Returns total number of records updated
- `isDryRun()`: Returns whether this was a dry run

#### BeforeEntityAnonymizeEvent

Dispatched once per entity class before processing its records.

**Properties**:
- `getEntityManager()`: Returns the EntityManagerInterface
- `getMetadata()`: Returns the ClassMetadata
- `getReflection()`: Returns the ReflectionClass
- `getEntityClass()`: Returns the entity class name
- `getTotalRecords()`: Returns total number of records for this entity
- `isDryRun()`: Returns whether this is a dry run

#### AfterEntityAnonymizeEvent

Dispatched once per entity class after processing its records.

**Properties**:
- `getEntityManager()`: Returns the EntityManagerInterface
- `getMetadata()`: Returns the ClassMetadata
- `getReflection()`: Returns the ReflectionClass
- `getEntityClass()`: Returns the entity class name
- `getProcessed()`: Returns number of records processed
- `getUpdated()`: Returns number of records updated
- `getPropertyStats()`: Returns statistics per property (property name => count)
- `isDryRun()`: Returns whether this was a dry run

#### AnonymizePropertyEvent

Dispatched before anonymizing each property, allowing listeners to modify the anonymized value or skip anonymization.

**Properties**:
- `getEntityManager()`: Returns the EntityManagerInterface
- `getMetadata()`: Returns the ClassMetadata
- `getProperty()`: Returns the ReflectionProperty
- `getPropertyName()`: Returns the property name
- `getColumnName()`: Returns the database column name
- `getOriginalValue()`: Returns the original value before anonymization
- `getAnonymizedValue()`: Returns the anonymized value
- `setAnonymizedValue(mixed $value)`: Modify the anonymized value
- `shouldSkipAnonymization()`: Returns whether anonymization should be skipped
- `setSkipAnonymization(bool $skip)`: Skip anonymization for this property
- `getRecord()`: Returns the full database record
- `isDryRun()`: Returns whether this is a dry run

### Example: Event Listener

```php
// src/EventListener/AnonymizeListener.php
namespace App\EventListener;

use Nowo\AnonymizeBundle\Event\AnonymizePropertyEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: AnonymizePropertyEvent::class)]
class AnonymizeListener
{
    public function onAnonymizeProperty(AnonymizePropertyEvent $event): void
    {
        // Modify the anonymized value
        if ($event->getPropertyName() === 'email') {
            $event->setAnonymizedValue('custom@example.com');
        }

        // Or skip anonymization for specific conditions
        if ($event->getRecord()['status'] === 'inactive') {
            $event->setSkipAnonymization(true);
        }
    }
}
```

### Example: Event Subscriber

```php
// src/EventSubscriber/AnonymizeSubscriber.php
namespace App\EventSubscriber;

use Nowo\AnonymizeBundle\Event\AfterAnonymizeEvent;
use Nowo\AnonymizeBundle\Event\BeforeAnonymizeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AnonymizeSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            BeforeAnonymizeEvent::class => 'onBeforeAnonymize',
            AfterAnonymizeEvent::class => 'onAfterAnonymize',
        ];
    }

    public function onBeforeAnonymize(BeforeAnonymizeEvent $event): void
    {
        // Log or modify entity classes before anonymization
        $entityClasses = $event->getEntityClasses();
        // ...
    }

    public function onAfterAnonymize(AfterAnonymizeEvent $event): void
    {
        // Log statistics or perform cleanup after anonymization
        $totalProcessed = $event->getTotalProcessed();
        $totalUpdated = $event->getTotalUpdated();
        // ...
    }
}
```

## Available Faker Types

The bundle supports 32 different faker types with various configuration options. See [FAKERS.md](FAKERS.md) for the complete list with detailed descriptions and configuration options for each faker type.
