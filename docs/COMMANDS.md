# Commands

> 📋 **Requirements**: This bundle requires **Symfony 6.1 or higher** (Symfony 6.0 is not supported). See [INSTALLATION.md](INSTALLATION.md) for complete requirements.

The bundle provides six console commands for managing anonymization, database exports, and history.

## Anonymize Command

Main command to anonymize database records.

```bash
php bin/console nowo:anonymize:run [options]
```

> **Note**: Currently supports Doctrine ORM connections (MySQL, PostgreSQL, SQLite). MongoDB ODM support is planned for future releases.

### Options

- `--connection, -c`: Process only specific connections (can be used multiple times)
- `--dry-run`: Show what would be anonymized without making changes
- `--batch-size, -b`: Number of records to process in each batch (default: 100)
- `--locale, -l`: Locale for Faker generator (default: en_US)
- `--stats-json`: Export statistics to JSON file
- `--stats-csv`: Export statistics to CSV file
- `--stats-only`: Show only statistics summary (suppress detailed output)
- `--no-progress`: Disable progress bar display
- `--verbose, -v`: Increase verbosity of messages
- `--debug`: Enable debug mode (shows detailed information)
- `--interactive, -i`: Enable interactive mode with step-by-step confirmations

### Examples

```bash
# Basic anonymization
php bin/console nowo:anonymize:run

# Dry-run to see what would be anonymized
php bin/console nowo:anonymize:run --dry-run

# Process specific connection
php bin/console nowo:anonymize:run --connection default

# Process multiple connections
php bin/console nowo:anonymize:run --connection default --connection postgres --connection sqlite

# Note: MongoDB connection is not yet supported (ODM support coming soon)
# php bin/console nowo:anonymize:run --connection mongodb  # Will show a warning

# Export statistics to JSON
php bin/console nowo:anonymize:run --stats-json stats.json

# Export statistics to CSV
php bin/console nowo:anonymize:run --stats-csv stats.csv

# Export statistics to both JSON and CSV
php bin/console nowo:anonymize:run --stats-json stats.json --stats-csv stats.csv

# Verbose mode with debug
php bin/console nowo:anonymize:run --verbose --debug

# Interactive mode with step-by-step confirmations
php bin/console nowo:anonymize:run --interactive

# Interactive mode with verbose output (shows property details)
php bin/console nowo:anonymize:run --interactive --verbose
```

### Interactive Mode

The `--interactive` (or `-i`) option enables step-by-step confirmation prompts:

1. **Initial Summary**: Shows a summary of what will be processed (entity managers, batch size, locale)
2. **Entity Manager Confirmation**: Asks for confirmation before processing each entity manager
3. **Entity Confirmation**: Asks for confirmation before processing each entity
4. **Entity Details**: In verbose mode, shows property details (faker types) for each entity

This mode is useful when you want to:
- Review what will be anonymized before proceeding
- Selectively process specific entity managers or entities
- Have more control over the anonymization process

Example output:
```
Interactive Mode - Anonymization Summary
Entity managers to process: default, postgres
Batch size: 100
Locale: en_US

Do you want to proceed with anonymization? (yes/no) [no]:
> yes

Processing entity manager: default
Found 3 entity(ies) to process in default:
  - App\Entity\User
  - App\Entity\Customer
  - App\Entity\Product

Do you want to process entity manager default? (yes/no) [yes]:
> yes

Entity: App\Entity\User (table: users, properties: 5)
  Properties to anonymize:
    - email (email)
    - firstName (name)
    - lastName (surname)
    - phone (phone)
    - age (age)

Do you want to process entity App\Entity\User? (yes/no) [yes]:
> yes
```

## Generate Column Migration Command

Generate SQL migrations to add the `anonymized` column to entities using `AnonymizableTrait`.

```bash
php bin/console nowo:anonymize:generate-column-migration [options]
```

### Options

- `--connection, -c`: Process only specific connections (can be used multiple times)
- `--output, -o`: Output SQL to a file instead of console

### Examples

```bash
# Generate migrations for all connections
php bin/console nowo:anonymize:generate-column-migration

# Generate migrations for specific connection
php bin/console nowo:anonymize:generate-column-migration --connection default

# Output to file
php bin/console nowo:anonymize:generate-column-migration --output migrations/add_anonymized_column.sql
```

## Generate MongoDB Field Script Command

Generate JavaScript scripts (compatible with mongosh) to add the `anonymized` field to MongoDB documents.

**⚠️ Note:** MongoDB ODM support is planned for future releases. This command currently works by scanning PHP document classes or accepting manual collection names.

```bash
php bin/console nowo:anonymize:generate-mongo-field [options]
```

### Options

- `--database, -d`: MongoDB database name (default: anonymize_demo)
- `--collection`: MongoDB collection name(s) to process (can be used multiple times)
- `--scan-documents`: Scan PHP document classes for `#[Anonymize]` attribute and collection names
- `--document-path`: Path to scan for document classes (default: src/Document)
- `--output, -o`: Output JavaScript to a file instead of console

### Examples

```bash
# Generate script for specific collections
php bin/console nowo:anonymize:generate-mongo-field --collection=user_activities --collection=users

# Scan document classes automatically
php bin/console nowo:anonymize:generate-mongo-field --scan-documents

# Specify database and save to file
php bin/console nowo:anonymize:generate-mongo-field --database=myapp --collection=user_activities --output=migration.js

# Execute the generated script
mongosh "mongodb://localhost:27017/anonymize_demo" < migration.js
```

### Generated Script

The command generates a JavaScript script that:
- Switches to the target database
- Uses `updateMany()` to add `anonymized: false` to all documents that don't have this field
- Prints progress and results for each collection

**Example output:**
```javascript
// MongoDB Script to Add Anonymized Field
// Generated: 2025-01-20 12:00:00
// Database: anonymize_demo
// Collections: user_activities

use('anonymize_demo');

// Add anonymized field to collection: user_activities
print('Processing collection: user_activities...');
const resultuser_activities = db.user_activities.updateMany(
    { anonymized: { $exists: false } },
    { $set: { anonymized: false } }
);
print(`  ✓ Updated ${resultuser_activities.modifiedCount} document(s) in user_activities`);

print('✅ Anonymized field migration completed successfully!');
```

## Info Command

Display detailed information about anonymizers defined in the application.

```bash
php bin/console nowo:anonymize:info [options]
```

### Options

- `--connection, -c`: Process only specific connections (can be used multiple times)
- `--locale, -l`: Locale for Faker generator (default: en_US)
- `--no-progress`: Disable progress bar display
- `--verbose, -v`: Increase verbosity of messages
- `--debug`: Enable debug mode (shows detailed information)

### What it shows

- Location of each anonymizer (entity and property)
- Configuration (faker type, options, patterns)
- Execution order (based on weight)
- Statistics about how many records will be anonymized

### Examples

```bash
# Show information about all anonymizers
php bin/console nowo:anonymize:info

# Show information for specific connection
php bin/console nowo:anonymize:info --connection default

# Verbose mode
php bin/console nowo:anonymize:info --verbose
```

## Statistics

The bundle provides detailed statistics about the anonymization process:

- **Total entities processed**: Number of entities that were scanned
- **Total records processed**: Number of database records processed
- **Total records updated**: Number of records that were anonymized
- **Total records skipped**: Number of records that didn't match patterns
- **Duration**: Time taken to complete the anonymization
- **Average per second**: Processing speed
- **Per-entity statistics**: Detailed stats for each entity
- **Per-property statistics**: Count of anonymizations per property

### Viewing Statistics

Statistics are automatically displayed after the anonymization completes:

```bash
php bin/console nowo:anonymize:run
```

### Export Statistics to JSON

Export statistics to a JSON file for further analysis:

```bash
php bin/console nowo:anonymize:run --stats-json stats.json
```

### Statistics Only Mode

Show only the statistics summary without detailed processing output:

```bash
php bin/console nowo:anonymize:run --stats-only
```

## Anonymization History Command

Command to view and manage anonymization history.

```bash
php bin/console nowo:anonymize:history [options]
```

### Options

- `--limit, -l`: Limit number of runs to display
- `--connection, -c`: Filter by connection name
- `--run-id`: View details of a specific run
- `--compare`: Compare two runs (comma-separated run IDs)
- `--cleanup`: Cleanup old runs
- `--days, -d`: Number of days to keep (for cleanup, default: 30)
- `--json`: Output as JSON

### Examples

```bash
# List all anonymization runs
php bin/console nowo:anonymize:history

# List the last 10 runs
php bin/console nowo:anonymize:history --limit 10

# Filter by connection
php bin/console nowo:anonymize:history --connection default

# View details of a specific run
php bin/console nowo:anonymize:history --run-id abc123def456

# Compare two runs
php bin/console nowo:anonymize:history --compare abc123,def456

# Cleanup runs older than 30 days
php bin/console nowo:anonymize:history --cleanup --days 30

# Output as JSON
php bin/console nowo:anonymize:history --json
```

### Features

- **Automatic History**: Every anonymization run is automatically saved to history
- **Run Details**: View complete statistics and metadata for each run
- **Comparison**: Compare two runs side-by-side to see differences
- **Filtering**: Filter runs by connection or limit results
- **Cleanup**: Remove old runs to manage storage
- **JSON Export**: Export history data in JSON format for further processing

### History Storage

History is stored in JSON format in the configured `history_dir` directory (default: `%kernel.project_dir%/var/anonymize_history`).

Each run includes:
- Run ID and timestamp
- Environment information (PHP version, Symfony version, environment)
- Command options used
- Complete statistics (global and per-entity)
- Duration and timing information

## Export Database Command

Command to export databases to files with optional compression.

```bash
php bin/console nowo:anonymize:export-db [options]
```

> **Note**: Supports MySQL, PostgreSQL, SQLite, and MongoDB. Requires appropriate command-line tools (mysqldump, pg_dump, mongodump) to be installed.

### Options

- `--connection, -c`: Specific connections to export (can be used multiple times, default: all)
- `--output-dir, -o`: Output directory for exports (default: configured in bundle config or `%kernel.project_dir%/var/exports`)
- `--filename-pattern`: Filename pattern for exports. Available placeholders: `{connection}`, `{database}`, `{date}`, `{time}`, `{format}` (default: configured in bundle config)
- `--compression`: Compression format: `none`, `gzip`, `bzip2`, `zip` (default: `gzip`)
- `--no-gitignore`: Skip updating `.gitignore` file

### Examples

```bash
# Export all databases with default settings
php bin/console nowo:anonymize:export-db

# Export specific connections
php bin/console nowo:anonymize:export-db --connection default --connection postgres

# Export with custom output directory and compression
php bin/console nowo:anonymize:export-db --output-dir /tmp/exports --compression zip

# Export without compression
php bin/console nowo:anonymize:export-db --compression none

# Export with custom filename pattern
php bin/console nowo:anonymize:export-db --filename-pattern "{database}_{date}.{format}"

# Export without updating .gitignore
php bin/console nowo:anonymize:export-db --no-gitignore
```

### Configuration

You can configure default export settings in your `config/packages/nowo_anonymize.yaml`:

```yaml
nowo_anonymize:
    export:
        enabled: true
        output_dir: '%kernel.project_dir%/var/exports'
        filename_pattern: '{connection}_{database}_{date}_{time}.{format}'
        compression: gzip  # none, gzip, bzip2, zip
        connections: []  # Empty array means all connections
        auto_gitignore: true
```

### Features

- **Automatic .gitignore**: Automatically creates/updates `.gitignore` to exclude export directory
- **Compression Support**: Supports gzip, bzip2, and zip compression (detects available tools)
- **Multiple Formats**: Exports MySQL (.sql), PostgreSQL (.sql), SQLite (.sqlite), MongoDB (.bson)
- **Flexible Naming**: Customizable filename patterns with placeholders
- **Selective Export**: Export specific connections or all connections
