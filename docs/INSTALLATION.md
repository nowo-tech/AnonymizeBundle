# Installation Guide

## Prerequisites

- PHP >= 8.1, < 8.6
- Symfony >= 6.0 || >= 7.0 || >= 8.0
- Doctrine ORM >= 2.13 || >= 3.0
- Doctrine Bundle >= 2.8

## Step 1: Install the Bundle

> ⚠️ **Important**: This bundle is **development-only**. Always install it as a dev dependency.

Install the bundle using Composer with the `--dev` flag:

```bash
composer require nowo-tech/anonymize-bundle --dev
```

## Step 2: Register the Bundle

> ⚠️ **Security**: Register the bundle **only for dev and test environments**. Never enable it in production.

If you're using Symfony Flex, the bundle is automatically registered for dev/test environments. Otherwise, manually register it in `config/bundles.php`:

```php
<?php

return [
    // ...
    Nowo\AnonymizeBundle\AnonymizeBundle::class => ['dev' => true, 'test' => true],
];
```

> ⚠️ **Security Note**: The bundle includes built-in protection that prevents execution in production environments. The command will automatically fail if run outside of `dev` or `test` environments, even if the bundle is registered.

## Step 3: Configure the Bundle (Optional)

If you're using Symfony Flex, the configuration file is automatically created at `config/packages/dev/nowo_anonymize.yaml`.

Otherwise, manually create the configuration file `config/packages/dev/nowo_anonymize.yaml`:

```yaml
nowo_anonymize:
    locale: 'en_US'
    connections: []
    dry_run: false
    batch_size: 100
```

See [CONFIGURATION.md](CONFIGURATION.md) for detailed configuration options.

## Step 4: Mark Your Entities

Add the `#[Anonymize]` attribute to entities you want to anonymize:

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
}
```

## Step 5: Run the Command

Test the installation with a dry-run:

```bash
php bin/console nowo:anonymize:run --dry-run
```

If everything looks good, run the actual anonymization:

```bash
php bin/console nowo:anonymize:run
```

## Verification

To verify the installation:

1. Check that the command is available:
   ```bash
   php bin/console list nowo:anonymize
   ```

2. Run a dry-run to see what would be anonymized:
   ```bash
   php bin/console nowo:anonymize:run --dry-run
   ```

3. Check your entities have the correct attributes:
   ```bash
   php bin/console debug:container --parameter=nowo_anonymize.locale
   ```

## Troubleshooting

### Command Not Found

If the command is not found, clear the cache:

```bash
php bin/console cache:clear
```

### No Entities Found

Make sure your entities have the `#[Anonymize]` attribute and at least one property has the `#[AnonymizeProperty]` attribute.

### Connection Errors

Verify your Doctrine connections are properly configured in `config/packages/doctrine.yaml`.

## Next Steps

- Read the [README.md](../README.md) for usage examples
- Check [CONFIGURATION.md](CONFIGURATION.md) for configuration options
- Review the demo project in the `demo/` directory
