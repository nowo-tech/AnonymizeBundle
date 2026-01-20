# Commands

The bundle provides three console commands for managing anonymization.

## Anonymize Command

Main command to anonymize database records.

```bash
php bin/console nowo:anonymize:run [options]
```

### Options

- `--connection, -c`: Process only specific connections (can be used multiple times)
- `--dry-run`: Show what would be anonymized without making changes
- `--batch-size, -b`: Number of records to process in each batch (default: 100)
- `--locale, -l`: Locale for Faker generator (default: en_US)
- `--stats-json`: Export statistics to JSON file
- `--stats-only`: Show only statistics summary (suppress detailed output)
- `--no-progress`: Disable progress bar display
- `--verbose, -v`: Increase verbosity of messages
- `--debug`: Enable debug mode (shows detailed information)

### Examples

```bash
# Basic anonymization
php bin/console nowo:anonymize:run

# Dry-run to see what would be anonymized
php bin/console nowo:anonymize:run --dry-run

# Process specific connection
php bin/console nowo:anonymize:run --connection default

# Export statistics to JSON
php bin/console nowo:anonymize:run --stats-json stats.json

# Verbose mode with debug
php bin/console nowo:anonymize:run --verbose --debug
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
