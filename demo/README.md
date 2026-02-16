# Anonymize Bundle Demos

This directory contains complete demos showing how to use the AnonymizeBundle with different Symfony versions and multiple databases.

## Demo Structure

Demos are organized by Symfony version. Each demo includes **multiple database connections** (MySQL, PostgreSQL, SQLite, and MongoDB) with pre-loaded test data, allowing you to test the bundle with different database systems in a single environment.

## Available Demos

### 1. Symfony 6 Demo (`symfony6`)

Complete demo with Symfony 6.1+, MySQL 8.0, and PostgreSQL 16.

**Features:**
- Symfony 6.1+ with all necessary dependencies
- MySQL 8.0 as default connection
- PostgreSQL 16 as secondary connection
- MongoDB 7.0 (infrastructure ready, ODM support coming soon)
- Docker Compose with all services configured
- Makefile with useful commands
- Example fixtures with test data
- Ready-to-use configuration with multiple connections

**Quick start:**
```bash
cd symfony6
make up      # Start containers
make setup   # Setup databases and load fixtures
make anonymize-dry-run  # Test anonymization
```

See [complete README](symfony6/README.md) for more details.

### 2. Symfony 7 Demo (`symfony7`)

Complete demo with Symfony 7.0, MySQL 8.0, and PostgreSQL 16.

**Features:**
- Symfony 7.0 with all necessary dependencies
- MySQL 8.0 as default connection
- PostgreSQL 16 as secondary connection
- MongoDB 7.0 (infrastructure ready, ODM support coming soon)
- Docker Compose with all services configured
- Makefile with useful commands
- Example fixtures with test data
- Ready-to-use configuration with multiple connections

**Quick start:**
```bash
cd symfony7
make up      # Start containers
make setup   # Setup databases and load fixtures
make anonymize-dry-run  # Test anonymization
```

See [complete README](symfony7/README.md) for more details.

### 3. Symfony 8 Demo (`symfony8`)

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

All demos share the following features:

### Runtime: FrankenPHP

Each demo runs the Symfony app with **[FrankenPHP](https://frankenphp.dev/)** (Caddy + PHP in worker mode) instead of nginx + PHP-FPM. A single container serves HTTP on port 80 (mapped to 8001/8000/8002 per demo) with `public/index.php` as the worker. No separate web server container is required.

### Multiple Database Connections

Each demo includes four database systems:

- **`default`**: MySQL connection (port 33061/33062/33063 depending on demo)
- **`postgres`**: PostgreSQL connection (port 54321/54322/54323 depending on demo)
- **`sqlite`**: SQLite connection (file-based: `var/data/anonymize_demo.sqlite`)
- **`mongodb`**: MongoDB connection (port 27016/27017/27018 depending on demo)

MySQL, PostgreSQL, and SQLite connections have the same entities and the same test data, allowing you to test the bundle with different database systems. MongoDB infrastructure is ready with Mongo Express for management, and documents are prepared for when the bundle supports MongoDB ODM.

### Example Entities

All demos include the same example entities:

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

All demos share the same Makefile commands:

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

## Demo Comparison

| Feature | Symfony 6 | Symfony 7 | Symfony 8 |
|---------|-----------|-----------|-----------|
| Symfony Version | 6.1+ | 7.0 | 8.0 |
| MySQL | 8.0 | 8.0 | 8.0 |
| PostgreSQL | 16 | 16 | 16 |
| SQLite | ✅ | ✅ | ✅ |
| MongoDB | 7.0 | 7.0 | 7.0 |
| MySQL Port | 33061 | 33062 | 33063 |
| PostgreSQL Port | 54321 | 54322 | 54323 |
| MongoDB Port | 27016 | 27019 | 27018 |
| Mongo Express Port | 8088 | 8087 | 8086 |
| Docker Compose | ✅ | ✅ | ✅ |
| Makefile | ✅ | ✅ | ✅ |
| Fixtures | ✅ | ✅ | ✅ |
| Multiple connections | ✅ | ✅ | ✅ |
| SQLite Support | ✅ | ✅ | ✅ |
| MongoDB Infrastructure | ✅ | ✅ | ✅ |
| MongoDB CRUD | ✅ | ✅ | ✅ |

All demos are functionally identical in terms of features, only the Symfony version used changes.

## Bundle code from the repo (development)

When you run the demos with Docker from this repository, they use the **bundle code from the repo** (not the published Packagist version). The PHP container mounts the bundle root at `/bundles`, and each demo's `composer.json` has a path repository `"url": "/bundles"` with `"nowo-tech/anonymize-bundle": "*"`. So after `make up` and `make install` (or `make setup`), the demo runs with the local bundle—ideal for testing changes like the new `excludePatterns` (array value and multiple configs).

## Important Notes

- **Bundle from repo**: With Docker, the bundle is resolved from the path `/bundles` (repo root). Run `make install` or `make setup` inside the demo so Composer installs/links the bundle from that path.
- **Test data**: Fixtures are automatically loaded in all SQL connections (MySQL, PostgreSQL, SQLite) when running `make setup`.
- **SQLite**: File-based database at `var/data/anonymize_demo.sqlite`, perfect for local development and testing.
- **MongoDB**: MongoDB infrastructure is ready with Mongo Express for management. Documents are prepared for when the bundle supports MongoDB ODM.
- **MongoDB CRUD**: Full CRUD interface available at `/mongodb/user-activity` to view and manage user activities with `anonymized` field tracking.
- **MongoDB Fixtures**: 30 user activities automatically loaded with `anonymized: false` field.
- **Multiple anonymization**: You can anonymize all SQL connections at once or select a specific one with `--connection`.
- **Development environment**: All demos are configured to run in development mode (`APP_ENV=dev`).

## Requirements

- Docker and Docker Compose
- Make (optional, but recommended for using Makefile commands)

## Verificación rápida (comprobar que las demos funcionan)

Desde la carpeta `demo/`, puedes comprobar que cada demo arranca y que el comando de anonimización está disponible:

```bash
# Symfony 6
cd symfony6 && composer install -n 2>/dev/null; php bin/console list nowo 2>&1 | head -5
cd ..

# Symfony 7
cd symfony7 && composer install -n 2>/dev/null; php bin/console list nowo 2>&1 | head -5
cd ..

# Symfony 8
cd symfony8 && composer install -n 2>/dev/null; php bin/console list nowo 2>&1 | head -5
```

O con Docker (recomendado; usa el bundle del repo montado en `/bundles`):

```bash
cd symfony6   # o symfony7 / symfony8
make up && make setup && make anonymize-dry-run
```

Si `php bin/console list` muestra los comandos `nowo:anonymize:*`, la aplicación y el bundle están correctamente cargados.

## Next Steps

1. Choose the demo corresponding to your Symfony version
2. Follow the instructions in that demo's specific README
3. Test anonymization with `make anonymize-dry-run`
