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

# Disable progress bar
php bin/console nowo:anonymize:run --no-progress

# Enable verbose mode
php bin/console nowo:anonymize:run --verbose

# Enable debug mode
php bin/console nowo:anonymize:run --debug
```

Command-line options take precedence over configuration file values.

## Available Commands

The bundle provides three console commands. See [COMMANDS.md](COMMANDS.md) for detailed command documentation, options, and examples.

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
