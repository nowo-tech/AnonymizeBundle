# Testing Commands Guide

This document describes how to test all commands of the AnonymizeBundle in the demo applications.

## Available Commands

The bundle provides 6 console commands:

1. **`nowo:anonymize:info`** - Display information about anonymizers defined in the application
2. **`nowo:anonymize:run`** - Execute anonymization of database records
3. **`nowo:anonymize:history`** - View and manage anonymization history
4. **`nowo:anonymize:export-db`** - Export databases to files with optional compression
5. **`nowo:anonymize:generate-column-migration`** - Generate Doctrine migration for anonymized column
6. **`nowo:anonymize:generate-mongo-field`** - Generate MongoDB field for anonymized tracking

## Testing Script

A testing script is available at the root of the project: `test-commands.sh`

### Usage

```bash
# Test all commands in all demos
./test-commands.sh all

# Test in a specific demo
./test-commands.sh demo-symfony6
./test-commands.sh demo-symfony7
./test-commands.sh demo-symfony8
```

### Prerequisites

1. Docker and Docker Compose must be installed
2. Demo containers must be running:
   ```bash
   cd demo/demo-symfony6 && docker-compose up -d
   cd demo/demo-symfony7 && docker-compose up -d
   cd demo/demo-symfony8 && docker-compose up -d
   ```

## Manual Testing

### 1. nowo:anonymize:info

Test information display for all connections:

```bash
# In each demo container
php bin/console nowo:anonymize:info --connection default
php bin/console nowo:anonymize:info --connection postgres
php bin/console nowo:anonymize:info --connection sqlite
php bin/console nowo:anonymize:info  # All connections
```

**Expected**: Should display information about all entities with `#[Anonymize]` attribute and their properties.

### 2. nowo:anonymize:run

Test anonymization execution (use `--dry-run` first):

```bash
# Dry-run mode (safe, no changes)
php bin/console nowo:anonymize:run --connection default --dry-run
php bin/console nowo:anonymize:run --connection postgres --dry-run
php bin/console nowo:anonymize:run --connection sqlite --dry-run

# With options
php bin/console nowo:anonymize:run --connection default --batch-size 50
php bin/console nowo:anonymize:run --connection default --locale es_ES
php bin/console nowo:anonymize:run --connection default --interactive
php bin/console nowo:anonymize:run --connection default --stats-json stats.json
php bin/console nowo:anonymize:run --connection default --stats-csv stats.csv
```

**Expected**: Should show what would be anonymized (dry-run) or actually anonymize data.

### 3. nowo:anonymize:history

Test history viewing:

```bash
php bin/console nowo:anonymize:history
php bin/console nowo:anonymize:history --limit 5
php bin/console nowo:anonymize:history --connection default
php bin/console nowo:anonymize:history show <run-id>
php bin/console nowo:anonymize:history compare <run-id-1> <run-id-2>
```

**Expected**: Should display anonymization run history.

### 4. nowo:anonymize:export-db

Test database export:

```bash
# Dry-run mode
php bin/console nowo:anonymize:export-db --connection default --dry-run
php bin/console nowo:anonymize:export-db --connection postgres --dry-run
php bin/console nowo:anonymize:export-db --connection sqlite --dry-run
php bin/console nowo:anonymize:export-db --connection mongodb --dry-run

# Actual export
php bin/console nowo:anonymize:export-db --connection default
php bin/console nowo:anonymize:export-db --all
```

**Expected**: Should export databases to files (with compression if available).

### 5. nowo:anonymize:generate-column-migration

Test migration generation:

```bash
php bin/console nowo:anonymize:generate-column-migration --entity App\\Entity\\User --connection default
php bin/console nowo:anonymize:generate-column-migration --entity App\\Entity\\Customer --connection postgres
```

**Expected**: Should generate a Doctrine migration file to add the `anonymized` column.

### 6. nowo:anonymize:generate-mongo-field

Test MongoDB field generation:

```bash
php bin/console nowo:anonymize:generate-mongo-field --document App\\Document\\UserActivity
php bin/console nowo:anonymize:generate-mongo-field --document App\\Document\\CustomerProfile
```

**Expected**: Should generate code to add `anonymized` field to MongoDB documents.

## Testing Checklist

For each demo (Symfony 6, 7, and 8):

- [ ] `nowo:anonymize:info` works for all connections (default, postgres, sqlite)
- [ ] `nowo:anonymize:run --dry-run` works for all connections
- [ ] `nowo:anonymize:run` with various options works
- [ ] `nowo:anonymize:history` displays history correctly
- [ ] `nowo:anonymize:export-db --dry-run` works for all connections
- [ ] `nowo:anonymize:generate-column-migration` generates correct migrations
- [ ] `nowo:anonymize:generate-mongo-field` generates correct field code
- [ ] All commands show help text correctly (`--help` option)
- [ ] Error handling works (invalid connections, missing entities, etc.)

## Common Issues

### Containers not running

```bash
cd demo/demo-symfony6 && docker-compose up -d
cd demo/demo-symfony7 && docker-compose up -d
cd demo/demo-symfony8 && docker-compose up -d
```

### Database not initialized

```bash
# In each demo container
php bin/console doctrine:schema:create
php bin/console doctrine:fixtures:load
```

### Permission issues

Ensure the script has execute permissions:
```bash
chmod +x test-commands.sh
```

## Notes

- Always use `--dry-run` first when testing `nowo:anonymize:run` to avoid modifying data
- The `--interactive` mode is useful for testing step-by-step execution
- Statistics export (`--stats-json`, `--stats-csv`) helps verify anonymization results
- History commands require at least one anonymization run to have data
