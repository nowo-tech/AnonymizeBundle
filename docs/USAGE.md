# Usage Guide

This guide covers how to use the Anonymize Bundle in your Symfony application.

> 📋 **Requirements**: This bundle requires **Symfony 6.1 or higher** (Symfony 6.0 is not supported). See [INSTALLATION.md](INSTALLATION.md) for complete requirements.

## Basic Setup

### Using FakerType Enum (Recommended)

For better type safety and IDE autocompletion, you can use the `FakerType` enum instead of strings:

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nowo\AnonymizeBundle\Attribute\Anonymize;
use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;
use Nowo\AnonymizeBundle\Enum\FakerType;

#[ORM\Entity]
#[Anonymize]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[AnonymizeProperty(type: FakerType::EMAIL, weight: 1)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[AnonymizeProperty(type: FakerType::NAME, weight: 2)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    #[AnonymizeProperty(type: FakerType::SURNAME, weight: 3)]
    private ?string $lastName = null;

    #[ORM\Column]
    #[AnonymizeProperty(type: FakerType::AGE, weight: 4, options: ['min' => 18, 'max' => 100])]
    private ?int $age = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[AnonymizeProperty(type: FakerType::PHONE, weight: 5)]
    private ?string $phone = null;

    #[ORM\Column(type: Types::STRING, length: 30, nullable: true)]
    #[AnonymizeProperty(
        type: FakerType::DNI_CIF,
        weight: 6,
        options: [
            'type' => 'dni',
            'preserve_null' => true,
        ]
    )]
    private ?string $legalId = null;
}
```

**Benefits of using FakerType enum:**
- ✅ Type safety: IDE autocompletion and type checking
- ✅ No typos: Compile-time validation
- ✅ Better refactoring: IDE can find all usages
- ✅ Self-documenting: All available types in one place

**Backward compatibility:** Strings still work, so existing code doesn't need to change.

### Using Strings (Still Supported)

You can still use strings if you prefer:

```php
#[ORM\Column(length: 255)]
#[AnonymizeProperty(type: 'email', weight: 1)]
private ?string $email = null;

#[ORM\Column(type: Types::STRING, length: 30, nullable: true)]
#[AnonymizeProperty(
    type: 'dni_cif',
    weight: 4,
    options: [
        'type' => 'dni',
        'preserve_null' => true,
    ]
)]
private ?string $legalId = null;
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

You can create custom faker services by implementing `FakerInterface`. The bundle includes a comprehensive example faker that demonstrates best practices.

#### Example Custom Faker

The bundle provides `ExampleCustomFaker` as a reference implementation located at:
```
src/Faker/Example/ExampleCustomFaker.php
```

This example demonstrates:
- How to preserve the original value (useful for testing events)
- How to access other fields from the current record
- How to access related entities using EntityManager
- How to implement custom anonymization logic

**To use it as a reference:**
1. Copy `src/Faker/Example/ExampleCustomFaker.php` to your project (e.g., `src/Service/YourCustomFaker.php`)
2. Change the namespace to match your project (e.g., `App\Service`)
3. Update the class name if needed
4. Implement your custom logic in the `generate()` method
5. Register the service in `services.yaml` or use `#[Autoconfigure(public: true)]`
6. Use it in your entity:

```php
use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;

#[AnonymizeProperty(
    type: 'service',
    service: 'App\Service\YourCustomFaker',
    weight: 1,
    options: [
        'preserve_original' => false,  // Set to true to preserve original value
        'custom_option' => 'value'
    ]
)]
#[ORM\Column(type: Types::STRING, length: 255)]
private ?string $customField = null;
```

**Accessing Data:**

The `$options` array in `generate()` contains:
- `original_value` (mixed): The original value of the field being anonymized (always provided)
- `record` (array): The full database record with all fields of the current entity (always provided)
- Any custom options passed via the `options` parameter in `#[AnonymizeProperty]`

**Example: Accessing other fields from the record**

```php
public function generate(array $options = []): mixed
{
    $originalValue = $options['original_value'] ?? null;
    $record = $options['record'] ?? [];
    
    // Access other fields from the current entity
    $otherField = $record['other_field'] ?? null;
    $relatedId = $record['related_entity_id'] ?? null;
    
    // Your custom anonymization logic
    return 'anonymized_value';
}
```

**Example: Accessing related entities**

```php
use Doctrine\ORM\EntityManagerInterface;
use Nowo\AnonymizeBundle\Faker\FakerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(public: true)]
class CustomFaker implements FakerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}
    
    public function generate(array $options = []): mixed
    {
        $record = $options['record'] ?? [];
        $relatedId = $record['related_entity_id'] ?? null;
        
        if ($relatedId) {
            $relatedEntity = $this->entityManager
                ->getRepository(RelatedEntity::class)
                ->find($relatedId);
            
            // Use related entity data in your anonymization logic
        }
        
        return 'anonymized_value';
    }
}
```

**Events:**

The bundle dispatches events that you can listen to for advanced customization:

- **AnonymizePropertyEvent**: Dispatched before anonymizing each property
  - Access via: `$event->getOriginalValue()`, `$event->getRecord()`, `$event->getEntityManager()`
  - Modify via: `$event->setAnonymizedValue($newValue)`
  - Skip via: `$event->setSkipAnonymization(true)`

- **BeforeAnonymizeEvent**: Dispatched before starting anonymization
- **AfterAnonymizeEvent**: Dispatched after completing anonymization
- **BeforeEntityAnonymizeEvent**: Dispatched before anonymizing an entity class
- **AfterEntityAnonymizeEvent**: Dispatched after anonymizing an entity class

**Example Event Listener:**

```php
use Nowo\AnonymizeBundle\Event\AnonymizePropertyEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: AnonymizePropertyEvent::class)]
class CustomAnonymizeListener
{
    public function onAnonymizeProperty(AnonymizePropertyEvent $event): void
    {
        // Access original value
        $originalValue = $event->getOriginalValue();
        
        // Access full record
        $record = $event->getRecord();
        
        // Access EntityManager for related entities
        $entityManager = $event->getEntityManager();
        
        // Modify the anonymized value
        $event->setAnonymizedValue('custom_value');
        
        // Or skip anonymization
        // $event->setSkipAnonymization(true);
    }
}
```

**Basic Custom Faker Implementation:**

If you don't need the advanced features, you can create a simple custom faker:

```php
use Nowo\AnonymizeBundle\Faker\FakerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(public: true)]
class CustomFaker implements FakerInterface
{
    public function generate(array $options = []): mixed
    {
        // Your custom anonymization logic
        $originalValue = $options['original_value'] ?? null;
        $record = $options['record'] ?? [];
        
        // Return anonymized value
        return 'anonymized_value';
    }
}
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

### Pattern-Based Faker

The `pattern_based` faker allows you to construct values based on other fields while preserving patterns from the original value. Perfect for cases where fields are derived from other fields but need to maintain certain patterns.

**Use Case**: When you have fields that are constructed from other fields (e.g., `username` from `email`) but need to preserve patterns from the original value (e.g., a number in parentheses).

**Example**:
```php
#[ORM\Entity]
#[Anonymize]
class User
{
    #[AnonymizeProperty(type: 'email', weight: 1)]
    public string $email;

    #[AnonymizeProperty(
        type: 'pattern_based',
        weight: 2,
        options: [
            'source_field' => 'email',  // Use anonymized email as base
            'pattern' => '/(\\(\\d+\\))$/',  // Extract (number) at the end
            'pattern_replacement' => '$1',  // Keep the extracted pattern
        ]
    )]
    public string $username;  // Original: "hola@pepe.com(15)" → Anonymized: "john@example.com(15)"

    #[AnonymizeProperty(
        type: 'pattern_based',
        weight: 3,
        options: [
            'source_field' => 'email',
            'pattern' => '/(\\(\\d+\\))$/',
            'pattern_replacement' => '$1',
        ]
    )]
    public string $usernameCanonical;  // Same pattern as username
}
```

**Options**:
- `source_field` (string, required): Name of the field to use as base (e.g., 'email')
- `pattern` (string): Regex pattern to extract from original_value (default: `'/(\\(\\d+\\))$/'` for parentheses with number)
- `pattern_replacement` (string): Replacement pattern for extracted value (default: `'$1'` to keep as-is)
- `separator` (string): Separator between source field and pattern (default: `''`)
- `fallback_faker` (string): Faker type to use if source field is null (default: `'username'`)
- `fallback_options` (array): Options for fallback faker (default: `[]`)

**Behavior**:
- Extracts a pattern from the original value using the regex pattern
- Uses the anonymized value of `source_field` as the base
- Appends the extracted pattern to create the new value
- If `source_field` is null, uses `fallback_faker` instead

**Pattern Examples**:
```php
// Extract number in parentheses: "email@test.com(15)" → "(15)"
'pattern' => '/(\\(\\d+\\))$/'

// Extract ID suffix: "user@test.com-ID123" → "-ID123"
'pattern' => '/-ID(\\d+)$/'
'pattern_replacement' => '-ID$1'

// Extract custom suffix: "email@test.com-user-42" → "-user-42"
'pattern' => '/-user-(\\d+)$/'
'pattern_replacement' => '-user-$1'
```

**Important Notes**:
- The `source_field` must be processed **before** the pattern_based field (use `weight` to control order)
- The `source_field` value in the record will be the **anonymized** value if it was already processed
- If the pattern doesn't match, no pattern is appended (just the source field value)
- The faker automatically receives the full record with already anonymized values

### UTM Faker

The `utm` faker generates anonymized UTM (Urchin Tracking Module) parameters for marketing campaign tracking. Perfect for anonymizing campaign tracking data while maintaining realistic parameter formats.

**Use Case**: Anonymizing marketing campaign tracking parameters (utm_source, utm_medium, utm_campaign, utm_term, utm_content) in analytics databases.

**Example**:

```php
#[ORM\Entity]
#[Anonymize]
class MarketingCampaign
{
    #[AnonymizeProperty(
        type: 'utm',
        weight: 1,
        options: [
            'type' => 'source',  // utm_source
            'format' => 'snake_case',
        ]
    )]
    #[ORM\Column(length: 100)]
    private ?string $utmSource = null;

    #[AnonymizeProperty(
        type: 'utm',
        weight: 2,
        options: [
            'type' => 'medium',  // utm_medium
            'format' => 'snake_case',
        ]
    )]
    #[ORM\Column(length: 100)]
    private ?string $utmMedium = null;

    #[AnonymizeProperty(
        type: 'utm',
        weight: 3,
        options: [
            'type' => 'campaign',  // utm_campaign
            'format' => 'snake_case',
            'min_length' => 5,
            'max_length' => 30,
        ]
    )]
    #[ORM\Column(length: 255)]
    private ?string $utmCampaign = null;

    #[AnonymizeProperty(
        type: 'utm',
        weight: 4,
        options: [
            'type' => 'term',  // utm_term (search term)
            'format' => 'snake_case',
            'min_length' => 3,
            'max_length' => 20,
        ]
    )]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $utmTerm = null;

    #[AnonymizeProperty(
        type: 'utm',
        weight: 5,
        options: [
            'type' => 'content',  // utm_content
            'format' => 'snake_case',
        ]
    )]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $utmContent = null;
}
```

**Options**:

- `type` (string, required): UTM parameter type - `'source'`, `'medium'`, `'campaign'`, `'term'`, or `'content'` (default: `'source'`)
- `format` (string): Format style - `'snake_case'`, `'kebab-case'`, `'camelCase'`, `'lowercase'`, or `'PascalCase'` (default: `'snake_case'`)
- `custom_sources` (array): Custom list of sources to use instead of defaults (for `type: 'source'`)
- `custom_mediums` (array): Custom list of mediums to use instead of defaults (for `type: 'medium'`)
- `custom_campaigns` (array): Custom list of campaign patterns to use instead of defaults (for `type: 'campaign'`)
- `prefix` (string): Optional prefix to add to the generated value
- `suffix` (string): Optional suffix to add to the generated value
- `min_length` (int): Minimum length for generated values (for campaign/term/content)
- `max_length` (int): Maximum length for generated values (for campaign/term/content)

**Default Values**:

- **Sources**: google, facebook, twitter, linkedin, instagram, youtube, newsletter, direct, referral, bing, yahoo, reddit, pinterest, tiktok, snapchat
- **Mediums**: cpc, cpm, email, social, organic, referral, affiliate, display, banner, retargeting, newsletter, sms, push, in-app, video, audio, print
- **Campaigns**: Uses predefined patterns (spring_sale, product_launch, etc.) or generates random campaign names

**Examples**:

```php
// UTM source with default format
#[AnonymizeProperty(type: 'utm', options: ['type' => 'source'])]

// UTM medium with kebab-case format
#[AnonymizeProperty(type: 'utm', options: ['type' => 'medium', 'format' => 'kebab-case'])]

// UTM campaign with custom length
#[AnonymizeProperty(type: 'utm', options: ['type' => 'campaign', 'min_length' => 10, 'max_length' => 25])]

// UTM source with custom sources list
#[AnonymizeProperty(type: 'utm', options: ['type' => 'source', 'custom_sources' => ['partner_a', 'partner_b', 'partner_c']])]

// UTM term with prefix
#[AnonymizeProperty(type: 'utm', options: ['type' => 'term', 'prefix' => 'kw_'])]
```

### Copy Faker

The `copy` faker allows you to copy the anonymized value from another field. Perfect for cases where multiple fields should have the same anonymized value (e.g., `email` and `emailCanonical`).

**Use Case**: When you have fields that should be identical after anonymization (e.g., canonical versions of fields).

**Example**:
```php
#[ORM\Entity]
#[Anonymize]
class User
{
    #[AnonymizeProperty(type: 'email', weight: 1)]
    public string $email;

    #[AnonymizeProperty(
        type: 'copy',
        weight: 2,
        options: [
            'source_field' => 'email',  // Copy from anonymized email
        ]
    )]
    public string $emailCanonical;  // Will be same as email after anonymization
}
```

**Options**:
- `source_field` (string, required): Name of the field to copy from (e.g., 'email')
- `fallback_faker` (string): Faker type to use if source field is null (default: 'email')
- `fallback_options` (array): Options for fallback faker (default: `[]`)

**Behavior**:
- Copies the anonymized value from `source_field`
- If `source_field` is null, uses `fallback_faker` to generate a value
- The `source_field` must be processed **before** the copy field (use `weight` to control order)

**Complete Example: Email and Username with Patterns**:
```php
#[ORM\Entity]
#[Anonymize]
class UserAccount
{
    // Email is anonymized first
    #[AnonymizeProperty(type: 'email', weight: 1)]
    public string $email;

    // Username is constructed from email + pattern from original username
    #[AnonymizeProperty(
        type: 'pattern_based',
        weight: 2,
        options: [
            'source_field' => 'email',
            'pattern' => '/(\\(\\d+\\))$/',  // Extract (15) from original
            'pattern_replacement' => '$1',
        ]
    )]
    public string $username;  // Original: "hola@pepe.com(15)" → "john@example.com(15)"

    // UsernameCanonical is same as username (same pattern)
    #[AnonymizeProperty(
        type: 'pattern_based',
        weight: 3,
        options: [
            'source_field' => 'email',
            'pattern' => '/(\\(\\d+\\))$/',
            'pattern_replacement' => '$1',
        ]
    )]
    public string $usernameCanonical;  // Same as username

    // EmailCanonical is same as email (copied)
    #[AnonymizeProperty(
        type: 'copy',
        weight: 4,
        options: [
            'source_field' => 'email',
        ]
    )]
    public string $emailCanonical;  // Same as email
}
```

**Result after anonymization**:
- `email`: `hola@pepe.com` → `john@example.com`
- `emailCanonical`: `hola@pepe.com` → `john@example.com` (same as email)
- `username`: `hola@pepe.com(15)` → `john@example.com(15)` (email + pattern)
- `usernameCanonical`: `hola@pepe.com(15)` → `john@example.com(15)` (same as username)

## Event System

The bundle provides a comprehensive event system for extensibility. You can listen to events to customize the anonymization process, modify anonymized values, or skip anonymization for specific conditions.

See [CONFIGURATION.md](CONFIGURATION.md) for complete event system documentation, including all available events, their properties, and detailed examples.
