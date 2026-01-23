# Pattern-Based and Copy Faker Examples

This document provides practical examples of using the `pattern_based` and `copy` fakers to construct values from other fields while preserving patterns or copying values.

## Example 1: Username from Email with Number Suffix

**Scenario**: You have a `User` entity where:
- `email` is anonymized with `email` faker
- `username` is constructed from `email` + a number in parentheses: `email@domain.com(15)`
- `usernameCanonical` follows the same pattern

**Solution**:
```php
<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
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

    #[AnonymizeProperty(type: 'email', weight: 1, excludePatterns: [
        'email' => '%@visitor.com'
    ])]
    #[ORM\Column(type: Types::STRING, length: 180)]
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
    #[ORM\Column(type: Types::STRING, length: 180, unique: true)]
    protected string $username;

    #[AnonymizeProperty(
        type: 'pattern_based',
        weight: 3,
        options: [
            'source_field' => 'email',
            'pattern' => '/(\\(\\d+\\))$/',
            'pattern_replacement' => '$1',
        ]
    )]
    #[ORM\Column(type: Types::STRING, length: 180, unique: true)]
    protected string $usernameCanonical;
}
```

**How it works**:
1. `email` is anonymized first (weight: 1) → `hola@pepe.com` → `john@example.com`
2. `username` is anonymized second (weight: 2):
   - Extracts `(15)` from original `username`: `hola@pepe.com(15)`
   - Uses anonymized `email`: `john@example.com`
   - Combines: `john@example.com(15)`
3. `usernameCanonical` is anonymized third (weight: 3):
   - Same process as `username`
   - Result: `john@example.com(15)`

## Example 2: User ID Suffix

**Scenario**: Username includes a user ID suffix: `email@domain.com-ID123`

```php
#[AnonymizeProperty(
    type: 'pattern_based',
    weight: 2,
    options: [
        'source_field' => 'email',
        'pattern' => '/-ID(\\d+)$/',  // Extract -ID123
        'pattern_replacement' => '-ID$1',  // Keep as -ID123
    ]
)]
public string $username;
```

**Result**: `old@email.com-ID123` → `new@example.com-ID123`

## Example 3: Custom Separator

**Scenario**: Username uses underscore separator: `email@domain.com_user-42`

```php
#[AnonymizeProperty(
    type: 'pattern_based',
    weight: 2,
    options: [
        'source_field' => 'email',
        'pattern' => '/_user-(\\d+)$/',  // Extract _user-42
        'pattern_replacement' => '_user-$1',  // Keep as _user-42
        'separator' => '',  // No additional separator needed
    ]
)]
public string $username;
```

**Result**: `old@email.com_user-42` → `new@example.com_user-42`

## Example 4: Fallback When Source is Null

**Scenario**: If email is null, use username faker as fallback:

```php
#[AnonymizeProperty(
    type: 'pattern_based',
    weight: 2,
    options: [
        'source_field' => 'email',
        'pattern' => '/(\\(\\d+\\))$/',
        'pattern_replacement' => '$1',
        'fallback_faker' => 'username',  // Use username faker if email is null
        'fallback_options' => ['min_length' => 5, 'max_length' => 15],
    ]
)]
public string $username;
```

**Result**: If email is null, generates a random username + extracted pattern

## Example 5: Complete User Account with Email and Username Canonical

**Scenario**: You have a `UserAccount` entity where:
- `email` is anonymized
- `emailCanonical` should be the same as `email` (copied)
- `username` is constructed from `email` + pattern from original `username`
- `usernameCanonical` should be the same as `username` (same pattern)

```php
#[ORM\Entity]
#[Anonymize]
class UserAccount
{
    #[AnonymizeProperty(type: 'email', weight: 1)]
    public string $email;

    #[AnonymizeProperty(
        type: 'pattern_based',
        weight: 2,
        options: [
            'source_field' => 'email',
            'pattern' => '/(\\(\\d+\\))$/',
            'pattern_replacement' => '$1',
        ]
    )]
    public string $username;

    #[AnonymizeProperty(
        type: 'pattern_based',
        weight: 3,
        options: [
            'source_field' => 'email',
            'pattern' => '/(\\(\\d+\\))$/',
            'pattern_replacement' => '$1',
        ]
    )]
    public string $usernameCanonical;

    #[AnonymizeProperty(
        type: 'copy',
        weight: 4,
        options: [
            'source_field' => 'email',
        ]
    )]
    public string $emailCanonical;
}
```

**How it works**:
1. `email` is anonymized first (weight: 1) → `hola@pepe.com` → `john@example.com`
2. `username` is anonymized second (weight: 2):
   - Extracts `(15)` from original: `hola@pepe.com(15)`
   - Uses anonymized `email`: `john@example.com`
   - Result: `john@example.com(15)`
3. `usernameCanonical` is anonymized third (weight: 3):
   - Same process as `username`
   - Result: `john@example.com(15)` (same as username)
4. `emailCanonical` is anonymized fourth (weight: 4):
   - Copies from anonymized `email`
   - Result: `john@example.com` (same as email)

## Example 6: Copy Faker - Simple Value Copying

**Scenario**: You have fields that should be identical after anonymization:

```php
#[AnonymizeProperty(
    type: 'copy',
    weight: 2,
    options: [
        'source_field' => 'email',
    ]
)]
public string $emailCanonical;
```

**Result**: `emailCanonical` will always have the same value as `email` after anonymization.

## Example 7: Copy Faker with Fallback

**Scenario**: If the source field is null, use a fallback faker:

```php
#[AnonymizeProperty(
    type: 'copy',
    weight: 2,
    options: [
        'source_field' => 'email',
        'fallback_faker' => 'email',
        'fallback_options' => ['local_part_length' => 10],
    ]
)]
public string $emailCanonical;
```

**Result**: If `email` is null, generates a new email using the email faker with custom options.

## Important Notes

1. **Weight Order**: The `source_field` must be processed **before** the `pattern_based` field. Use `weight` to ensure correct order:
   - `email` → weight: 1
   - `username` → weight: 2 (uses anonymized email)
   - `usernameCanonical` → weight: 3 (uses anonymized email)

2. **Pattern Matching**: The regex pattern must match the pattern you want to extract. Use regex capture groups `()` to extract specific parts.

3. **Pattern Replacement**: Use `$1`, `$2`, etc. to reference captured groups in the replacement pattern.

4. **Record Access**: The faker automatically receives the full record with already anonymized values, so `source_field` will contain the anonymized value if it was processed earlier.
