# Usage Guide

This guide covers how to use the Anonymize Bundle in your Symfony application.

## Basic Setup

1. **Mark an entity for anonymization** with the `#[Anonymize]` attribute:

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nowo\AnonymizeBundle\Attribute\Anonymize;
use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;

#[ORM\Entity]
#[Anonymize]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[AnonymizeProperty(type: 'email', weight: 1)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[AnonymizeProperty(type: 'name', weight: 2)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    #[AnonymizeProperty(type: 'surname', weight: 3)]
    private ?string $lastName = null;

    #[ORM\Column]
    #[AnonymizeProperty(type: 'age', weight: 4, options: ['min' => 18, 'max' => 100])]
    private ?int $age = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[AnonymizeProperty(type: 'phone', weight: 5)]
    private ?string $phone = null;
}
```

2. **Run the anonymization command**:

```bash
php bin/console nowo:anonymize:run
```

## Advanced Usage

### Pattern-based Filtering

You can specify which records to anonymize using inclusion/exclusion patterns:

```php
#[ORM\Entity]
#[Anonymize(
    includePatterns: ['status' => 'active'],
    excludePatterns: ['id' => '<=100']
)]
class User
{
    #[ORM\Column(length: 255)]
    #[AnonymizeProperty(
        type: 'email',
        includePatterns: ['role' => 'admin'],
        excludePatterns: ['id' => '=1']
    )]
    private ?string $email = null;
}
```

### Weight-based Ordering

Properties with lower weights are anonymized first. Properties without weights are processed last, alphabetically:

```php
#[AnonymizeProperty(type: 'email', weight: 1)]      // Processed first
#[AnonymizeProperty(type: 'name', weight: 2)]       // Processed second
#[AnonymizeProperty(type: 'phone')]                 // Processed last (no weight)
```

### Custom Service Faker

You can use a custom service for anonymization:

```php
#[AnonymizeProperty(type: 'service', service: 'app.custom_anonymizer')]
private ?string $customField = null;
```

The service must implement `FakerInterface` or have a `generate()` method.

### Multiple Connections

The bundle automatically processes all Doctrine connections. You can also specify specific connections:

```bash
php bin/console nowo:anonymize:run --connection default --connection secondary
```

### Anonymization Tracking

Track which records have been anonymized using the `AnonymizableTrait`:

```php
use Nowo\AnonymizeBundle\Trait\AnonymizableTrait;

#[ORM\Entity]
#[Anonymize]
class User
{
    use AnonymizableTrait;
    // ... your properties
}
```

**Generate migration for the `anonymized` column**:

```bash
php bin/console nowo:anonymize:generate-column-migration
```

This command will:
1. Scan all entities using `AnonymizableTrait`
2. Check if the `anonymized` column already exists
3. Generate SQL migrations to add the column if missing

**After anonymization**, records are automatically marked with `anonymized = true`. You can query them:

```sql
SELECT * FROM users WHERE anonymized = true;
```

Or check programmatically:

```php
if ($user->isAnonymized()) {
    // This record has been anonymized
}
```

## Pattern Matching

Patterns support the following operators:

- `>`: Greater than (e.g., `'id' => '>100'`)
- `>=`: Greater than or equal
- `<`: Less than
- `<=`: Less than or equal
- `=`: Equal to
- `!=` or `<>`: Not equal to
- `%`: SQL LIKE pattern (e.g., `'name' => 'John%'`)

## Event System

The bundle provides a comprehensive event system for extensibility. You can listen to events to customize the anonymization process, modify anonymized values, or skip anonymization for specific conditions.

See [CONFIGURATION.md](CONFIGURATION.md) for complete event system documentation, including all available events, their properties, and detailed examples.
