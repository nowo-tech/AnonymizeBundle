# Anonymize Bundle Demos

This directory contains a complete demo showing how to use the AnonymizeBundle with Symfony and multiple databases.

## Demo Structure

The demo is organized under `symfony8/`. It includes **multiple database connections** (MySQL, PostgreSQL, SQLite, and MongoDB) with pre-loaded test data, allowing you to test the bundle with different database systems in a single environment.

## Available Demo

### Symfony 8 Demo (`symfony8`)

Complete demo with Symfony 8.0, MySQL 8.0, and PostgreSQL 16.

**Features:**
- Symfony 8.0 with all necessary dependencies
- MySQL 8.0 as default connection
- PostgreSQL 16 as secondary connection
- MongoDB 7.0 (infrastructure ready, ODM support coming soon)
- Docker Compose with all services configured
- Makefile with useful commands
- Example fixtures with test data
- Ready-to-use configuration with multiple connections

**Quick start:**
```bash
cd symfony8
make up      # Start containers
make setup   # Setup databases and load fixtures
make anonymize-dry-run  # Test anonymization
```

See [complete README](symfony8/README.md) for more details.

## Common Features

### Runtime: FrankenPHP

The demo runs the Symfony app with **[FrankenPHP](https://frankenphp.dev/)** (Caddy + PHP in worker mode) instead of nginx + PHP-FPM. A single container serves HTTP on port 80 (mapped to 8002) with `public/index.php` as the worker. No separate web server container is required.

### Multiple Database Connections

The demo includes four database systems:

- **`default`**: MySQL connection (hostname `mysql` on the Compose network; not published on the host)
- **`postgres`**: PostgreSQL connection (hostname `postgres` on the Compose network; not published on the host)
- **`sqlite`**: SQLite connection (file-based: `var/data/anonymize_demo.sqlite`)
- **`mongodb`**: MongoDB connection (hostname `mongodb` on the Compose network; optional host port 27018 for local tools)

MySQL, PostgreSQL, and SQLite connections have the same entities and the same test data, allowing you to test the bundle with different database systems. From your machine, use **phpMyAdmin** / **pgAdmin** or `docker compose exec php …` to query MySQL/PostgreSQL. MongoDB infrastructure is ready with Mongo Express for management, and documents are prepared for when the bundle supports MongoDB ODM.

### Example Entities

#### User Entity

The `User` entity demonstrates basic bundle usage with different faker types:

- **email**: Anonymized with `EmailFaker` (weight 1)
- **firstName**: Anonymized with `NameFaker` (weight 2)
- **lastName**: Anonymized with `SurnameFaker` (weight 3)
- **age**: Anonymized with `AgeFaker` (weight 4, range 18-100)
- **phone**: Anonymized with `PhoneFaker` (weight 5)
- **iban**: Anonymized with `IbanFaker` (weight 6, country ES)
- **creditCard**: Anonymized with `CreditCardFaker` (weight 7)

All properties have defined weights to control anonymization order.

#### Customer Entity

The `Customer` entity demonstrates inclusion/exclusion pattern usage:

- Only records with `status = 'active'` are anonymized
- Records with `id <= 10` are excluded
- The email has additional patterns: only anonymized if `status = 'active'` and `id != 1`

### Common Commands

#### Container Management
```bash
make up          # Start Docker containers
make down        # Stop Docker containers
make shell       # Open shell in PHP container
make logs        # View container logs
```

#### Installation and Setup
```bash
make install     # Install dependencies
make setup       # Complete setup (install + DB + fixtures)
make clean       # Clean vendor, var and cache
```

#### Anonymization
```bash
make anonymize-dry-run    # Test anonymization (dry-run)
make anonymize            # Run real anonymization
make anonymize-stats      # Run with statistics export
```

#### Database
```bash
make db-create     # Create databases
make db-drop       # Drop databases
make db-reset      # Reset databases (drop + create + schema + fixtures)
make db-fixtures   # Load fixtures
make db-view       # View current records in both connections
```

## Anonymization Command Options

```bash
# Anonymize only a specific connection
php bin/console nowo:anonymize:run --connection default
php bin/console nowo:anonymize:run --connection postgres

# Anonymize multiple connections
php bin/console nowo:anonymize:run --connection default --connection postgres

# Dry-run mode (only shows what would be anonymized)
php bin/console nowo:anonymize:run --dry-run

# Change batch size
php bin/console nowo:anonymize:run --batch-size 50

# Change locale for Faker
php bin/console nowo:anonymize:run --locale en_US

# Export statistics to JSON
php bin/console nowo:anonymize:run --stats-json stats.json

# Show only statistics (quiet mode)
php bin/console nowo:anonymize:run --stats-only
```

## Viewing Results

After running the command, you can verify the anonymized data in both connections:

```bash
# View data in MySQL (default connection)
docker-compose exec php php bin/console dbal:run-sql "SELECT * FROM users" --connection=default
docker-compose exec php php bin/console dbal:run-sql "SELECT * FROM customers" --connection=default

# View data in PostgreSQL (postgres connection)
docker-compose exec php php bin/console dbal:run-sql "SELECT * FROM users" --connection=postgres
docker-compose exec php php bin/console dbal:run-sql "SELECT * FROM customers" --connection=postgres

# Or use the Makefile command (shows both connections)
make db-view
```

The data should be anonymized according to the attributes defined in the entities.

## Demo Overview

| Feature | Symfony 8 |
|---------|-----------|
| Symfony Version | 8.0 |
| MySQL | 8.0 |
| PostgreSQL | 16 |
| SQLite | ✅ |
| MongoDB | 7.0 |
| MySQL / PostgreSQL published on host | No (Compose network + admin UIs) |
| MongoDB host port (optional) | 27018 |
| Mongo Express Port | 8086 |
| Docker Compose | ✅ |
| Makefile | ✅ |
| Fixtures | ✅ |
| Multiple connections | ✅ |
| SQLite Support | ✅ |
| MongoDB Infrastructure | ✅ |
| MongoDB CRUD | ✅ |

## Bundle code from the repo (development)

When you run the demo with Docker from this repository, it uses the **bundle code from the repo** (not the published Packagist version). The PHP container mounts the bundle root at `/bundles`, and the demo's `composer.json` has a path repository `"url": "/bundles"` with `"nowo-tech/anonymize-bundle": "*"`. So after `make up` and `make install` (or `make setup`), the demo runs with the local bundle—ideal for testing changes like the new `excludePatterns` (array value and multiple configs).

## Important Notes

- **Bundle from repo**: With Docker, the bundle is resolved from the path `/bundles` (repo root). Run `make install` or `make setup` inside the demo so Composer installs/links the bundle from that path.
- **Test data**: Fixtures are automatically loaded in all SQL connections (MySQL, PostgreSQL, SQLite) when running `make setup`.
- **SQLite**: File-based database at `var/data/anonymize_demo.sqlite`, perfect for local development and testing.
- **MongoDB**: MongoDB infrastructure is ready with Mongo Express for management. Documents are prepared for when the bundle supports MongoDB ODM.
- **MongoDB CRUD**: Full CRUD interface available at `/mongodb/user-activity` to view and manage user activities with `anonymized` field tracking.
- **MongoDB Fixtures**: 30 user activities automatically loaded with `anonymized: false` field.
- **Multiple anonymization**: You can anonymize all SQL connections at once or select a specific one with `--connection`.
- **Development environment**: The demo is configured to run in development mode (`APP_ENV=dev`).

## Requirements

- Docker and Docker Compose
- Make (optional, but recommended for using Makefile commands)

## Quick verification (check that the demo works)

From the `demo/` folder, you can verify that the demo starts and that the anonymization command is available:

```bash
# Symfony 8
cd symfony8 && composer install -n 2>/dev/null; php bin/console list nowo 2>&1 | head -5
```

Or with Docker (recommended; uses the bundle from the repo mounted at `/bundles`):

```bash
cd symfony8
make up && make setup && make anonymize-dry-run
```

If `php bin/console list` shows the `nowo:anonymize:*` commands, the application and bundle are loaded correctly.

## Next Steps

1. Open `demo/symfony8` and follow its README
2. Test anonymization with `make anonymize-dry-run`
