# Anonymize Bundle Demo - Symfony 8

This demo shows how to use the AnonymizeBundle with Symfony 8.0, including multiple database connections (MySQL, PostgreSQL, SQLite, and MongoDB).

## Features

- **Symfony 8.0** with all necessary dependencies
- **MySQL 8.0** as default connection
- **PostgreSQL 16** as secondary connection
- **SQLite** as file-based connection
- **MongoDB 7.0** (infrastructure ready, ODM support coming soon)
- **phpMyAdmin** to view and manage MySQL (port 8084)
- **pgAdmin** to view and manage PostgreSQL (port 8085)
- **Mongo Express** to view and manage MongoDB (port 8086)
- **Web CRUD** complete interface to manage Users and Customers in each connection
- **Docker Compose** with all services configured
- **Makefile** with useful commands for development
- **Example fixtures** with test data
- **Ready-to-use configuration** with multiple connections

## Requirements

- Docker and Docker Compose
- Make (optional, but recommended)

## Quick Start

### 1. Start containers

```bash
make up
```

This will automatically create the `.env` file if it doesn't exist and start all containers (PHP, MySQL, PostgreSQL, phpMyAdmin, pgAdmin).

### 2. Setup the demo

```bash
make setup
```

This command:
- Installs Composer dependencies
- Creates databases (MySQL and PostgreSQL)
- Creates schemas in both databases
- Loads test data (fixtures) using DoctrineFixturesBundle

### 3. Access Web CRUD

Once containers are up, nginx is automatically running and serving the application.

Access the web application at: **http://localhost:8000**

The nginx server is configured to serve the Symfony application from the `public` directory.

### 4. Test anonymization

```bash
# Dry-run mode (only shows what would be anonymized)
make anonymize-dry-run

# Run real anonymization
make anonymize
```

If you see **"Unknown named parameter $truncate"**, the demo’s `vendor` has an older copy of the bundle. Update it from the repo:

```bash
make update-bundle
```

Then run `make anonymize-dry-run` again.

## Connection Structure

This demo includes four database systems:

- **`default`**: MySQL connection (port 33063)
- **`postgres`**: PostgreSQL connection (port 54323)
- **`sqlite`**: SQLite connection (file-based: `var/data/anonymize_demo.sqlite`)
- **`mongodb`**: MongoDB connection (port 27018) - Infrastructure ready, ODM support coming soon

MySQL, PostgreSQL, and SQLite connections have the same entities (`User` and `Customer`) and the same test data. MongoDB infrastructure is ready with Mongo Express for management, and a sample document (`UserActivity`) is prepared for when the bundle supports MongoDB ODM.

## Web CRUD

The demo includes a complete CRUD accessible from the browser to manage entities in each connection:

### Available Routes

- **Home**: `/` - Main page with links to all sections
- **Users MySQL**: `/default/user` - Users CRUD in MySQL
- **Users PostgreSQL**: `/postgres/user` - Users CRUD in PostgreSQL
- **Users SQLite**: `/sqlite/user` - Users CRUD in SQLite
- **Customers MySQL**: `/default/customer` - Customers CRUD in MySQL
- **Customers PostgreSQL**: `/postgres/customer` - Customers CRUD in PostgreSQL
- **Customers SQLite**: `/sqlite/customer` - Customers CRUD in SQLite
- **User Activities MongoDB**: `/mongodb/user-activity` - User Activities CRUD in MongoDB

### CRUD Features

- **List** all entities from each connection
- **Create** new entities
- **View** entity details
- **Edit** existing entities
- **Delete** entities (with confirmation)

Each page clearly shows which connection you're working with via a color badge.

## Web Interfaces for Databases

### phpMyAdmin (MySQL)

Access phpMyAdmin at: **http://localhost:8084**

**Credentials:**
- Username: `demo_user` (or the value of `MYSQL_USER`)
- Password: `password` (or the value of `MYSQL_PASSWORD`)

phpMyAdmin automatically connects to the MySQL database. You can:
- View `users` and `customers` tables
- Query data before and after anonymization
- Execute SQL queries directly
- Export/import data

### pgAdmin (PostgreSQL)

Access pgAdmin at: **http://localhost:8085**

### Mongo Express (MongoDB)

Access Mongo Express at: **http://localhost:8086**

**Access credentials:**
- Username: `admin` (or the value of `MONGO_EXPRESS_USER`)
- Password: `admin` (or the value of `MONGO_EXPRESS_PASSWORD`)

Mongo Express automatically connects to the MongoDB database. You can:
- View collections
- Browse documents
- Query data
- Manage indexes

**Note**: MongoDB ODM support is coming soon. Currently, MongoDB infrastructure is ready and a sample document (`UserActivity`) is prepared in `src/Document/UserActivity.php` for when the bundle supports MongoDB ODM.

The `UserActivity` document includes an `anonymized` field (similar to `AnonymizableTrait` in ORM entities) to track anonymization status. The CRUD interface displays this status for each document.

**Access credentials:**
- Email: `admin@example.com` (or the value of `PGADMIN_EMAIL`)
- Password: `admin` (or the value of `PGADMIN_PASSWORD`)

**PostgreSQL server configuration:**
1. Once inside pgAdmin, right-click on "Servers" → "Register" → "Server"
2. In the "General" tab:
   - Name: `PostgreSQL Demo`
3. In the "Connection" tab:
   - Host name/address: `postgres`
   - Port: `5432`
   - Maintenance database: `anonymize_demo`
   - Username: `demo_user` (or the value of `POSTGRES_USER`)
   - Password: `password` (or the value of `POSTGRES_PASSWORD`)
   - Check "Save password"
4. Click "Save"

Now you can:
- View `users` and `customers` tables
- Query data before and after anonymization
- Execute SQL queries directly
- View table structure

## Available Commands

### Container Management

```bash
make up          # Start Docker containers
make down        # Stop Docker containers
make shell       # Open shell in PHP container
make logs        # View container logs
```

### Installation and Setup

```bash
make install     # Install dependencies
make setup       # Complete setup (install + DB + fixtures)
make clean       # Clean vendor, var and cache
```

### Anonymization

```bash
make anonymize-dry-run    # Test anonymization (dry-run)
make anonymize            # Run real anonymization
make anonymize-stats      # Run with statistics export
```

### Database

```bash
make db-create     # Create databases
make db-drop       # Drop databases
make db-reset      # Reset databases (drop + create + schema + fixtures)
make db-fixtures   # Load fixtures using DoctrineFixturesBundle
make db-view       # View current records in both connections
```

**Note:** Fixtures are loaded using `doctrine:fixtures:load` which automatically loads fixtures in both connections (MySQL and PostgreSQL).

## Example Entities

### User Entity

The `User` entity demonstrates basic bundle usage with different faker types:

- **email**: Anonymized with `EmailFaker` (weight 1)
- **firstName**: Anonymized with `NameFaker` (weight 2)
- **lastName**: Anonymized with `SurnameFaker` (weight 3)
- **age**: Anonymized with `AgeFaker` (weight 4, range 18-100)
- **phone**: Anonymized with `PhoneFaker` (weight 5)
- **iban**: Anonymized with `IbanFaker` (weight 6, country ES)
- **creditCard**: Anonymized with `CreditCardFaker` (weight 7)
- **anonymized**: Boolean field to track anonymization status (uses `AnonymizableTrait`)

### Customer Entity

The `Customer` entity demonstrates inclusion/exclusion pattern usage:

- Only records with `status = 'active'` are anonymized
- Records with `id <= 10` are excluded
- The email has additional patterns: only anonymized if `status = 'active'` and `id != 1`
- **anonymized**: Boolean field to track anonymization status (uses `AnonymizableTrait`)

## Anonymized Column Tracking

Both `User` and `Customer` entities use the `AnonymizableTrait` to track anonymization status. This feature allows you to:

- Track which records have been anonymized
- Query anonymized records
- Validate anonymization processes

### Adding the Anonymized Column

To add the `anonymized` column to your entities, use the provided command:

```bash
# Generate migration for a specific connection
php bin/console nowo:anonymize:generate-column-migration --connection default
php bin/console nowo:anonymize:generate-column-migration --connection postgres

# Apply the generated migration
php bin/console doctrine:migrations:migrate --connection default
php bin/console doctrine:migrations:migrate --connection postgres
```

### Using the Column

Once the column is added:

- The bundle automatically sets `anonymized = true` when a record is anonymized
- The Web CRUD interface displays the anonymization status
- You can query records by anonymization status

**Note:** The Web CRUD interface will show a warning if the `anonymized` column doesn't exist, with instructions on how to add it.

## Viewing Results

After running the command, you can verify anonymized data in several ways:

### Option 1: Web Interfaces (Recommended)

**phpMyAdmin (MySQL):**
1. Open http://localhost:8080
2. Select the `anonymize_demo` database
3. Navigate to `users` or `customers` tables
4. You'll see anonymized data in real-time

**pgAdmin (PostgreSQL):**
1. Open http://localhost:8081
2. Connect to the PostgreSQL server (see configuration above)
3. Navigate to `Databases` → `anonymize_demo` → `Schemas` → `public` → `Tables`
4. Right-click on `users` or `customers` → "View/Edit Data" → "All Rows"
5. You'll see anonymized data in real-time

### Option 2: Command Line

```bash
# View data in MySQL (default connection)
docker-compose exec php php bin/console dbal:run-sql "SELECT * FROM users" --connection=default
docker-compose exec php php bin/console dbal:run-sql "SELECT * FROM customers" --connection=default

# View data in PostgreSQL (postgres connection)
docker-compose exec php php bin/console dbal:run-sql "SELECT * FROM users" --connection=postgres
docker-compose exec php php bin/console dbal:run-sql "SELECT * FROM customers" --connection=postgres

# Or use the Makefile command
make db-view
```

### Recommended Workflow

1. **Before anonymizing**: Open phpMyAdmin and pgAdmin to view original data
2. **Run anonymization**: `make anonymize` (or `make anonymize-dry-run` first)
3. **After anonymizing**: Refresh pages in phpMyAdmin and pgAdmin to see changes

## Anonymization Command Options

```bash
# Anonymize only a specific connection
php bin/console nowo:anonymize:run --connection default
php bin/console nowo:anonymize:run --connection postgres

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

## Configuration

### Environment Variables

The `.env` file is automatically created when running `make up`. You can modify it if you need to change credentials or configurations:

```env
APP_ENV=dev
APP_SECRET=your-secret-key-change-this-in-production
DATABASE_URL=mysql://demo_user:password@mysql:3306/anonymize_demo?serverVersion=8.0&charset=utf8mb4
DATABASE_URL_POSTGRES=postgresql://demo_user:password@postgres:5432/anonymize_demo?serverVersion=16&charset=utf8
DATABASE_URL_SQLITE=sqlite:///%kernel.project_dir%/var/data/anonymize_demo.sqlite
MONGODB_URL=mongodb://demo_user:password@mongodb:27017/anonymize_demo?authSource=admin
MYSQL_ROOT_PASSWORD=password
MYSQL_DATABASE=anonymize_demo
MYSQL_USER=demo_user
MYSQL_PASSWORD=password
POSTGRES_USER=demo_user
POSTGRES_PASSWORD=password
POSTGRES_DB=anonymize_demo
MONGODB_USER=demo_user
MONGODB_PASSWORD=password
MONGODB_DATABASE=anonymize_demo
MONGO_EXPRESS_USER=admin
MONGO_EXPRESS_PASSWORD=admin
```

### Bundle Configuration

Bundle configuration is in `config/packages/dev/nowo_anonymize.yaml`:

```yaml
nowo_anonymize:
    locale: 'es_ES'
    connections: []              # Empty = process all connections
    dry_run: false
    batch_size: 100
```

## Clean and Restart

```bash
# Stop containers and clean
make down
make clean

# Restart everything
make up
make setup
```

## Notes

- The bundle is included as a dependency and will be installed automatically with `make install`.
- Test data is automatically loaded in all SQL connections (MySQL, PostgreSQL, SQLite).
- MongoDB infrastructure is ready with Mongo Express for management. A sample document (`UserActivity`) is prepared in `src/Document/UserActivity.php` for when the bundle supports MongoDB ODM.
- MongoDB fixtures are automatically loaded (30 user activities) with `anonymized: false` field.
- You can anonymize all SQL connections at once or select a specific one with `--connection`.
- MongoDB CRUD is available at `/mongodb/user-activity` to view and manage user activities.
