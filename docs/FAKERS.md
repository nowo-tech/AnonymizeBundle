# Faker Types

> 📋 **Requirements**: This bundle requires **Symfony 6.1 or higher** (Symfony 6.0 is not supported). See [INSTALLATION.md](INSTALLATION.md) for complete requirements.

The bundle supports 32 different faker types for anonymizing various data types.

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

## Data Preservation Fakers

- **hash_preserve**: Deterministic anonymization using hash functions (maintains referential integrity)
  - Options: `algorithm` (md5/sha1/sha256/sha512), `salt`, `preserve_format`, `length`
- **shuffle**: Shuffle values within a column while maintaining distribution
  - Options: `values` (required), `seed` (for reproducibility), `exclude`
- **constant**: Replace with constant value
  - Options: `value` (required, can be any type including null)

## Custom Fakers

- **service**: Uses a custom service for anonymization (requires `service` option with service name)

The service must implement `FakerInterface` or have a `generate()` method.

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
