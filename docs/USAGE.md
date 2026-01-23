# Usage Guide

This guide covers how to use the Anonymize Bundle in your Symfony application.

> 📋 **Requirements**: This bundle requires **Symfony 6.1 or higher** (Symfony 6.0 is not supported). See [INSTALLATION.md](INSTALLATION.md) for complete requirements.

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

    #[ORM\Column(length: 255, nullable: true)]
    #[AnonymizeProperty(
        type: 'email',
        weight: 6,
        options: ['nullable' => true, 'null_probability' => 20]
    )]
    private ?string $optionalEmail = null; // 20% chance of being null
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

## Specialized Fakers

### Name Fallback Faker

The `name_fallback` faker handles cases where an entity has multiple name fields (e.g., `name` and `firstname`) where one can be nullable. It ensures data consistency by generating a random value for the null field when the related field has a value.

**Use Case**: When you have entities with nullable related name fields and want to ensure both fields are populated after anonymization.

**Example**:
```php
#[ORM\Entity]
#[Anonymize]
class Person
{
    #[ORM\Column(length: 255, nullable: true)]
    #[AnonymizeProperty(
        type: 'name_fallback',
        options: ['fallback_field' => 'firstname', 'gender' => 'random']
    )]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[AnonymizeProperty(
        type: 'name_fallback',
        options: ['fallback_field' => 'name', 'gender' => 'random']
    )]
    private ?string $firstname = null;
}
```

**Behavior**:
- If `name = 'John'` and `firstname = null` → generates a random `firstname`
- If `name = null` and `firstname = 'Jane'` → generates a random `name`
- If both are `null` → generates both randomly
- If both have values → anonymizes both normally

**Options**:
- `fallback_field` (required): Name of the related field to check (e.g., `'firstname'` or `'name'`)
- `gender` (optional): `'male'`, `'female'`, or `'random'` (default: `'random'`)
- `locale_specific` (optional): Use locale-specific names (default: `true`)

### DNI/CIF/NIF Faker

The `dni_cif` faker generates anonymized Spanish identification numbers (DNI, CIF, or NIF) with proper format validation.

**Use Case**: Anonymizing Spanish personal or company identification numbers while maintaining the correct format.

**Example**:
```php
#[ORM\Entity]
#[Anonymize]
class Customer
{
    #[ORM\Column(length: 20, nullable: true)]
    #[AnonymizeProperty(
        type: 'dni_cif',
        options: ['type' => 'auto', 'formatted' => false]
    )]
    private ?string $documentId = null;
}
```

**Types**:
- `'dni'`: Generates DNI format (8 digits + 1 letter, e.g., `12345678A`)
- `'cif'`: Generates CIF format (1 letter + 7 digits + 1 letter/digit, e.g., `A12345674`)
- `'nif'`: Same as DNI (8 digits + 1 letter)
- `'auto'`: Auto-detects type from original value if available, otherwise defaults to DNI

**Options**:
- `type` (optional): `'dni'`, `'cif'`, `'nif'`, or `'auto'` (default: `'auto'`)
- `formatted` (optional): Add separators (default: `false`)
  - DNI formatted: `12345678-A`
  - CIF formatted: `A-1234567-4`

**Examples**:
```php
// DNI with formatting
#[AnonymizeProperty(type: 'dni_cif', options: ['type' => 'dni', 'formatted' => true])]

// CIF without formatting
#[AnonymizeProperty(type: 'dni_cif', options: ['type' => 'cif', 'formatted' => false])]

// Auto-detect from original value
#[AnonymizeProperty(type: 'dni_cif', options: ['type' => 'auto'])]
```

### HTML Faker

The `html` faker generates anonymized HTML content with lorem ipsum text. Perfect for anonymizing email signatures, HTML templates, and other HTML content.

**Use Case**: Anonymizing email signatures, HTML email bodies, HTML templates, or any HTML content while maintaining valid HTML structure.

**Example**:
```php
#[ORM\Entity]
#[Anonymize]
class EmailSignature
{
    #[ORM\Column(type: 'text', nullable: true)]
    #[AnonymizeProperty(
        type: 'html',
        options: [
            'type' => 'signature',
            'include_links' => true,
            'include_styles' => false,
        ]
    )]
    private ?string $signature = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[AnonymizeProperty(
        type: 'html',
        options: [
            'type' => 'paragraph',
            'min_paragraphs' => 1,
            'max_paragraphs' => 3,
            'include_links' => true,
        ]
    )]
    private ?string $emailBody = null;
}
```

**Types**:
- `'signature'`: Generates email signature-like HTML with name, title, company, and contact info (default)
- `'paragraph'`: Generates HTML paragraphs with lorem ipsum
- `'list'`: Generates HTML lists (ul/ol) with lorem ipsum items
- `'mixed'`: Generates mixed HTML content (paragraphs, lists, headings)

**Options**:
- `type` (optional): `'signature'`, `'paragraph'`, `'list'`, or `'mixed'` (default: `'signature'`)
- `include_links` (optional): Include hyperlinks in HTML (default: `true`)
- `include_styles` (optional): Include inline styles (default: `false`)
- `min_paragraphs` (optional): Minimum number of paragraphs (default: `1`)
- `max_paragraphs` (optional): Maximum number of paragraphs (default: `3`)
- `min_list_items` (optional): Minimum number of list items (default: `2`)
- `max_list_items` (optional): Maximum number of list items (default: `5`)

**Examples**:
```php
// Email signature with links
#[AnonymizeProperty(type: 'html', options: ['type' => 'signature', 'include_links' => true])]

// Email signature with styles
#[AnonymizeProperty(type: 'html', options: ['type' => 'signature', 'include_styles' => true])]

// HTML paragraphs
#[AnonymizeProperty(type: 'html', options: ['type' => 'paragraph', 'min_paragraphs' => 2, 'max_paragraphs' => 5])]

// HTML list
#[AnonymizeProperty(type: 'html', options: ['type' => 'list', 'min_list_items' => 3, 'max_list_items' => 7])]

// Mixed HTML content
#[AnonymizeProperty(type: 'html', options: ['type' => 'mixed', 'include_styles' => true])]
```

**Signature Output Example**:
```html
<div>
    <p><strong>John Doe</strong><br>
    Senior Developer<br>
    Tech Solutions Inc.</p>
    <p>Phone: <a href="tel:+34612345678">+34 612 345 678</a><br>
    Email: <a href="mailto:john.doe@example.com">john.doe@example.com</a><br>
    Website: <a href="https://www.example.com">www.example.com</a></p>
</div>
```

### Nullable Option

All fakers support a `nullable` option that allows you to generate `null` values with a configurable probability. This is useful for simulating real-world data where some fields may be optional.

**Options**:
- `nullable` (bool): Enable null value generation (default: `false`)
- `null_probability` (int): Probability of generating null (0-100, default: `0`)
  - `0` = never null (always generates a value)
  - `100` = always null (never generates a value)
  - `30` = 30% chance of being null, 70% chance of generating a value

**Example**:
```php
#[ORM\Entity]
#[Anonymize]
class User
{
    #[ORM\Column(length: 255, nullable: true)]
    #[AnonymizeProperty(
        type: 'email',
        options: [
            'nullable' => true,
            'null_probability' => 20  // 20% chance of being null
        ]
    )]
    private ?string $optionalEmail = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[AnonymizeProperty(
        type: 'phone',
        options: [
            'nullable' => true,
            'null_probability' => 50  // 50% chance of being null
        ]
    )]
    private ?string $phone = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[AnonymizeProperty(
        type: 'html',
        options: [
            'type' => 'signature',
            'nullable' => true,
            'null_probability' => 10  // 10% chance of being null
        ]
    )]
    private ?string $emailSignature = null;
}
```

**Use Cases**:
- Simulating optional fields in your database
- Testing how your application handles null values
- Creating more realistic anonymized datasets
- Matching the null distribution of your original data

**Note**: The `nullable` option works with all faker types. When a value is determined to be null, it bypasses the faker generation and sets the field to `null` directly.

### Preserve Null Option

All fakers support a `preserve_null` option that allows you to skip anonymization when the original value is `null`. This is useful when you want to anonymize only fields that have values, leaving null values unchanged.

**Options**:
- `preserve_null` (bool): Skip anonymization if the original value is null (default: `false`)
  - `true` = If original value is null, skip anonymization (preserve the null)
  - `false` = Always anonymize, even if original value is null (generate a new value)

**Example**:
```php
#[ORM\Entity]
#[Anonymize]
class User
{
    #[ORM\Column(length: 255, nullable: true)]
    #[AnonymizeProperty(
        type: 'email',
        options: ['preserve_null' => true]  // Skip if null, anonymize if has value
    )]
    private ?string $optionalEmail = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[AnonymizeProperty(
        type: 'dni_cif',
        options: [
            'type' => 'dni',
            'preserve_null' => true  // Only anonymize if DNI exists
        ]
    )]
    private ?string $legalId = null;
}
```

**Behavior**:
- If original value is `null` and `preserve_null` is `true` → Skip anonymization (field remains null)
- If original value has a value and `preserve_null` is `true` → Anonymize normally
- If `preserve_null` is `false` (default) → Always anonymize, even if original is null

**Use Cases**:
- Anonymizing only fields that have values, preserving nulls
- Maintaining data structure where nulls have meaning
- Selective anonymization based on data presence

**Note**: The `preserve_null` option works with all faker types and takes precedence over `nullable` option. If `preserve_null` is `true` and the original value is null, the field is skipped entirely (not added to updates).

## Event System

The bundle provides a comprehensive event system for extensibility. You can listen to events to customize the anonymization process, modify anonymized values, or skip anonymization for specific conditions.

See [CONFIGURATION.md](CONFIGURATION.md) for complete event system documentation, including all available events, their properties, and detailed examples.
