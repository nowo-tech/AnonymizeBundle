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

The bundle supports **two levels of pattern matching** that work together:

1. **Entity-level patterns** (`#[Anonymize]`): Determine if a **record** is a candidate for anonymization
2. **Property-level patterns** (`#[AnonymizeProperty]`): Determine if a **property** should be anonymized (only when the record is already a candidate)

#### How Pattern Combination Works

The anonymization process follows this logic:

1. **First**: Check if the record matches entity-level patterns
   - If **NO** → Skip the entire record (nothing is anonymized)
   - If **YES** → Continue to step 2

2. **Then**: For each property with `#[AnonymizeProperty]`, check if it matches property-level patterns
   - If **YES** → Anonymize the property
   - If **NO** → Skip the property (leave it unchanged)

**Important**: A property is only anonymized when **BOTH** conditions are met:
- The record matches entity-level patterns (if defined)
- The property matches its own patterns (if defined)

#### Examples

**Example 1: Entity-level filtering only**

```php
#[ORM\Entity]
#[Anonymize(
    includePatterns: ['status' => 'active'],
    excludePatterns: ['id' => '<=100']
)]
class User
{
    #[ORM\Column(length: 255)]
    #[AnonymizeProperty(type: 'email')]  // No property-level patterns
    private ?string $email = null;
    
    #[ORM\Column(length: 255)]
    #[AnonymizeProperty(type: 'name')]   // No property-level patterns
    private ?string $firstName = null;
}
```

**Result**:
- Records with `status='active'` AND `id>100` → **Both** `email` and `firstName` are anonymized
- Records with `status!='active'` OR `id<=100` → **Nothing** is anonymized (entire record skipped)

**Example 2: Property-level filtering only**

```php
#[ORM\Entity]
#[Anonymize]  // No entity-level patterns (all records are candidates)
class User
{
    #[ORM\Column(length: 255)]
    #[AnonymizeProperty(
        type: 'email',
        includePatterns: ['role' => 'admin']  // Only admins
    )]
    private ?string $email = null;
    
    #[ORM\Column(length: 255)]
    #[AnonymizeProperty(
        type: 'name',
        includePatterns: ['status' => 'active']  // Only active users
    )]
    private ?string $firstName = null;
}
```

**Result**:
- All records are candidates (no entity-level filter)
- Records with `role='admin'` → `email` is anonymized, `firstName` depends on `status`
- Records with `status='active'` → `firstName` is anonymized, `email` depends on `role`
- Records with `role='admin'` AND `status='active'` → **Both** are anonymized

**Example 3: Combined entity and property patterns**

```php
#[ORM\Entity]
#[Anonymize(
    includePatterns: ['status' => 'active'],  // Only active users
    excludePatterns: ['id' => '<=100']        // Exclude first 100 records
)]
class User
{
    #[ORM\Column(length: 255)]
    #[AnonymizeProperty(
        type: 'email',
        includePatterns: ['role' => 'admin']  // Only admins
    )]
    private ?string $email = null;
    
    #[ORM\Column(length: 255)]
    #[AnonymizeProperty(
        type: 'name',
        includePatterns: ['department' => 'IT']  // Only IT department
    )]
    private ?string $firstName = null;
}
```

**Result** (all conditions must be met):

| Record Conditions | Entity Match? | email Anonymized? | firstName Anonymized? |
|------------------|---------------|-------------------|----------------------|
| `status='active'`, `id>100`, `role='admin'`, `department='IT'` | ✅ YES | ✅ YES | ✅ YES |
| `status='active'`, `id>100`, `role='admin'`, `department='Sales'` | ✅ YES | ✅ YES | ❌ NO |
| `status='active'`, `id>100`, `role='user'`, `department='IT'` | ✅ YES | ❌ NO | ✅ YES |
| `status='active'`, `id>100`, `role='user'`, `department='Sales'` | ✅ YES | ❌ NO | ❌ NO |
| `status='inactive'`, `id>100`, `role='admin'`, `department='IT'` | ❌ NO | ❌ NO | ❌ NO |
| `status='active'`, `id=50`, `role='admin'`, `department='IT'` | ❌ NO | ❌ NO | ❌ NO |

**Example 4: Complex scenario with OR operator**

```php
#[ORM\Entity]
#[Anonymize(
    includePatterns: ['status' => 'inactive|unsubscribed']  // Multiple statuses
)]
class EmailSubscription
{
    #[ORM\Column(length: 255)]
    #[AnonymizeProperty(
        type: 'email',
        includePatterns: ['email' => '%@test-domain.com|%@example.com']  // Multiple domains
    )]
    private ?string $email = null;
    
    #[ORM\Column(length: 255, nullable: true)]
    #[AnonymizeProperty(
        type: 'email',
        includePatterns: ['status' => 'unsubscribed']  // Only if unsubscribed
    )]
    private ?string $backupEmail = null;
}
```

**Result**:
- Records with `status='inactive'` OR `status='unsubscribed'` → Entity-level match
  - If `email` contains `@test-domain.com` OR `@example.com` → `email` is anonymized
  - If `status='unsubscribed'` → `backupEmail` is anonymized
  - If `status='inactive'` → `backupEmail` is NOT anonymized (doesn't match property pattern)

#### Pattern Matching Rules Summary

1. **Entity-level patterns** (`#[Anonymize]`):
   - If **empty** → All records are candidates
   - If **defined** → Only matching records are candidates
   - **Exclusions take precedence** over inclusions

2. **Property-level patterns** (`#[AnonymizeProperty]`):
   - If **empty** → Property is anonymized (when record is candidate)
   - If **defined** → Property is anonymized only when patterns match
   - **Exclusions take precedence** over inclusions

3. **Combination logic**:
   - Record must match entity patterns **AND** property must match its patterns
   - If entity patterns don't match → **Nothing** is anonymized
   - If entity patterns match but property patterns don't → **Only that property** is skipped

#### Common Use Cases

**Use Case 1: Anonymize all properties for specific records**
```php
#[Anonymize(includePatterns: ['status' => 'archived'])]
class User
{
    #[AnonymizeProperty(type: 'email')]    // No patterns = always anonymized when record matches
    #[AnonymizeProperty(type: 'name')]      // No patterns = always anonymized when record matches
}
```

**Use Case 2: Anonymize specific properties for all records**
```php
#[Anonymize]  // No patterns = all records are candidates
class User
{
    #[AnonymizeProperty(type: 'email', includePatterns: ['role' => 'admin'])]  // Only admins
    #[AnonymizeProperty(type: 'name')]  // All users
}
```

**Use Case 3: Conditional anonymization based on multiple criteria**
```php
#[Anonymize(includePatterns: ['status' => 'inactive|archived'])]
class User
{
    #[AnonymizeProperty(type: 'email', includePatterns: ['email' => '%@old-domain.com'])]  // Old domain emails
    #[AnonymizeProperty(type: 'phone', includePatterns: ['country' => 'US|CA'])]  // US/Canada phones
}
```

### Weight-based Ordering

Properties with lower weights are anonymized first. Properties without weights are processed last, alphabetically:

```php
#[AnonymizeProperty(type: 'email', weight: 1)]      // Processed first
#[AnonymizeProperty(type: 'name', weight: 2)]       // Processed second
#[AnonymizeProperty(type: 'phone')]                 // Processed last (no weight)
```

### Relationship Patterns

You can use patterns that reference related entities using dot notation (e.g., `type.name`, `category.code`):

```php
#[ORM\Entity]
#[Anonymize(
    // Anonymize orders where the related Type entity's name contains 'HR'
    includePatterns: ['type.name' => '%HR', 'status' => 'completed']
)]
class Order
{
    #[ORM\ManyToOne]
    private ?Type $type = null;
    
    #[ORM\Column(length: 50)]
    private ?string $status = null;
    
    #[AnonymizeProperty(type: 'email')]
    private ?string $customerEmail = null;
}
```

**How it works:**
- The bundle automatically detects relationship patterns (fields with dots)
- It builds SQL queries with `LEFT JOIN` clauses to access related entity fields
- Patterns work with all comparison operators and SQL LIKE patterns
- Multiple relationship levels are supported (e.g., `order.customer.address.city`)

**Example with multiple relationships:**
```php
#[Anonymize(includePatterns: [
    'type.name' => '%HR',           // Type name contains 'HR'
    'customer.status' => 'active',   // Customer is active
    'status' => 'completed'          // Order is completed
])]
class Order
{
    #[ORM\ManyToOne]
    private ?Type $type = null;
    
    #[ORM\ManyToOne]
    private ?Customer $customer = null;
    
    #[ORM\Column(length: 50)]
    private ?string $status = null;
}
```

**Note:** Relationship patterns require that the association exists in Doctrine metadata. The bundle will skip patterns for non-existent associations.

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
