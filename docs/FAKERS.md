# Faker Types

> 📋 **Requirements**: This bundle requires **Symfony 6.1 or higher** (Symfony 6.0 is not supported). See [INSTALLATION.md](INSTALLATION.md) for complete requirements.

The bundle supports 39 different faker types for anonymizing various data types.

> 💡 **Tip**: All fakers support the `nullable` and `null_probability` options to generate null values with a configurable probability. See [USAGE.md](USAGE.md#nullable-option) for details.

## Using FakerType Enum (Recommended)

For better type safety and IDE autocompletion, use the `FakerType` enum instead of strings:

```php
use Nowo\AnonymizeBundle\Enum\FakerType;
use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;

// Using enum (recommended)
#[AnonymizeProperty(type: FakerType::DNI_CIF, options: ['type' => 'dni', 'preserve_null' => true])]
private ?string $legalId = null;

// Using string (still supported for backward compatibility)
#[AnonymizeProperty(type: 'dni_cif', options: ['type' => 'dni', 'preserve_null' => true])]
private ?string $legalId = null;
```

**Benefits of using FakerType enum:**
- ✅ IDE autocompletion and type checking
- ✅ Compile-time validation (no typos)
- ✅ Better refactoring support
- ✅ Self-documenting (all available types in one place)

**Backward compatibility:** Strings still work, so existing code doesn't need to change.

## Basic Fakers

- **email**: Generates anonymized email addresses
- **name**: Generates anonymized first names
- **surname**: Generates anonymized surnames
- **age**: Generates anonymized ages (supports `min` and `max` options)
- **phone**: Generates anonymized phone numbers
- **iban**: Generates anonymized IBAN numbers (supports `country` option)
- **credit_card**: Generates anonymized credit card numbers

## Advanced Fakers

- **address**: Generates anonymized street addresses (supports `country`, `format`, `include_postal_code` options)
- **date**: Generates anonymized dates (supports `min_date`, `max_date`, `format`, `type` options)
- **username**: Generates anonymized usernames (supports `min_length`, `max_length`, `prefix`, `suffix`, `include_numbers` options)
- **url**: Generates anonymized URLs (supports `scheme`, `domain`, `path` options)
- **company**: Generates anonymized company names (supports `type`, `suffix` options)
- **masking**: Partial masking of sensitive data (supports `preserve_start`, `preserve_end`, `mask_char`, `mask_length` options)
- **password**: Generates anonymized passwords (supports `length`, `include_special`, `include_numbers`, `include_uppercase` options)
- **ip_address**: Generates anonymized IP addresses (supports `version` (4/6), `type` (public/private/localhost) options)
- **mac_address**: Generates anonymized MAC addresses (supports `separator` (colon/dash/none), `uppercase` options)
- **uuid**: Generates anonymized UUIDs (supports `version` (1/4), `format` (with_dashes/without_dashes) options)
- **hash**: Generates anonymized hash values (supports `algorithm` (md5/sha1/sha256/sha512), `length` options)
- **coordinate**: Generates anonymized GPS coordinates (supports `format` (array/string/json), `precision`, `bounds` options)
- **color**: Generates anonymized color values (supports `format` (hex/rgb/rgba), `alpha` options)
- **boolean**: Generates anonymized boolean values (supports `true_probability` (0-100) option)
- **numeric**: Generates anonymized numeric values (supports `type` (int/float), `min`, `max`, `precision` options)
- **file**: Generates anonymized file paths and names (supports `extension`, `directory`, `absolute` options)
- **json**: Generates anonymized JSON structures (supports `schema`, `depth`, `max_items` options)
- **text**: Generates anonymized text content (supports `type` (sentence/paragraph), `min_words`, `max_words` options)
- **enum**: Generates values from a predefined list (supports `values` (array), `weighted` (associative array) options)
- **country**: Generates anonymized country codes/names (supports `format` (code/name/iso2/iso3), `locale` options)
- **language**: Generates anonymized language codes/names (supports `format` (code/name), `locale` options)

## Specialized Fakers

- **dni_cif**: Generates anonymized Spanish DNI, CIF, or NIF numbers
  - Options: `type` (dni/cif/nif/auto), `formatted` (add separators)
  - Auto-detects type from original value if available
  - DNI format: 8 digits + 1 letter (e.g., `12345678A`)
  - CIF format: 1 letter + 7 digits + 1 letter/digit (e.g., `A12345674`)
  - Example: `['type' => 'dni', 'formatted' => true]` generates `12345678-A`

- **name_fallback**: Handles nullable related name fields with fallback logic
  - Options: `fallback_field` (required, name of related field), `gender` (male/female/random), `locale_specific` (bool)
  - Ensures data consistency: if one name field has value and the other is null, generates a random value for the null field
  - Perfect for entities with `name` and `firstname` where one can be nullable
  - Example: `['fallback_field' => 'firstname', 'gender' => 'random']`

- **html**: Generates anonymized HTML content with lorem ipsum
  - Options: `type` (signature/paragraph/list/mixed), `include_links` (bool), `include_styles` (bool), `min_paragraphs`, `max_paragraphs`, `min_list_items`, `max_list_items`
  - Perfect for anonymizing email signatures, HTML templates, and HTML content
  - Generates valid HTML with lorem ipsum text while maintaining realistic structure
  - Signature type includes name, title, company, contact info with optional links
  - Example: `['type' => 'signature', 'include_links' => true, 'include_styles' => false]`

- **pattern_based**: Constructs values from other fields with pattern extraction
  - Options: `source_field` (required), `pattern` (regex), `pattern_replacement`, `separator`, `fallback_faker`, `fallback_options`
  - Perfect for fields derived from other fields that need to preserve patterns (e.g., username from email with number suffix)
  - Extracts a pattern from the original value and appends it to the anonymized source field value
  - Example: `['source_field' => 'email', 'pattern' => '/(\\(\\d+\\))$/']` - preserves number in parentheses

- **copy**: Copies value from another field
  - Options: `source_field` (required), `fallback_faker`, `fallback_options`
  - Perfect for fields that should be identical after anonymization (e.g., email and emailCanonical)
  - Simply copies the anonymized value from the source field
  - Example: `['source_field' => 'email']` - copies anonymized email value

- **utm**: Generates anonymized UTM (Urchin Tracking Module) parameters
  - Options: `type` (source/medium/campaign/term/content), `format` (snake_case/kebab-case/camelCase/lowercase/PascalCase), `custom_sources`, `custom_mediums`, `custom_campaigns`, `prefix`, `suffix`, `min_length`, `max_length`
  - Perfect for anonymizing marketing campaign tracking parameters
  - Supports all UTM parameter types: source, medium, campaign, term, and content
  - Example: `['type' => 'source', 'format' => 'snake_case']` - generates utm_source value

## Data Preservation Fakers

- **hash_preserve**: Deterministic anonymization using hash functions (maintains referential integrity)
  - Options: `algorithm` (md5/sha1/sha256/sha512), `salt`, `preserve_format`, `length`
- **shuffle**: Shuffle values within a column while maintaining distribution
  - Options: `values` (required), `seed` (for reproducibility), `exclude`
- **constant**: Replace with constant value
  - Options: `value` (required, can be any type including null)

- **map**: Replace values using a mapping ("if value is X, put Y")
  - Options: `map` (required, associative array `original_value => replacement_value`), `default` (optional, value when original is not in map; if omitted, unmapped values are left as-is)
  - Use when you want to anonymize by substituting each original value with a fixed replacement (e.g. status 'active' → 'status_a', 'inactive' → 'status_b')
  - Example: `['map' => ['active' => 'status_a', 'inactive' => 'status_b', 'pending' => 'status_c'], 'default' => 'status_unknown']`

## Custom Fakers

- **service**: Uses a custom service for anonymization (requires `service` option with service name)
  - The service must implement `FakerInterface` or have a `generate()` method
  - See [USAGE.md](USAGE.md#custom-service-faker) for detailed examples and best practices
  - The bundle includes `ExampleCustomFaker` as a reference implementation at `src/Faker/Example/ExampleCustomFaker.php`
  - This example demonstrates:
    - How to preserve the original value (useful for testing events)
    - How to access other fields from the current record
    - How to access related entities using EntityManager
    - How to implement custom anonymization logic

## Enhanced Fakers

Some fakers have been enhanced with additional options:

- **IbanFaker**: Added `valid` and `formatted` options
- **AgeFaker**: Added `distribution` (uniform/normal), `mean`, and `std_dev` options
- **NameFaker**: Added `gender` (male/female/random) and `locale_specific` options
- **SurnameFaker**: Added `gender` and `locale_specific` options
- **EmailFaker**: Added `domain`, `format` (name.surname/random), and `local_part_length` options
- **PhoneFaker**: Added `country_code`, `format` (international/national), and `include_extension` options
- **CreditCardFaker**: Added `type` (visa/mastercard/amex/random), `valid`, and `formatted` options

For detailed configuration options for each faker, see [CONFIGURATION.md](CONFIGURATION.md).
