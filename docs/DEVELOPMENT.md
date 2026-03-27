# Development Guide

This guide covers development setup, testing, and code quality for the Anonymize Bundle.

## Table of contents

- [Development Setup](#development-setup)
  - [Using Docker (Recommended)](#using-docker-recommended)
  - [Without Docker](#without-docker)
- [Testing](#testing)
  - [Test Statistics](#test-statistics)
  - [Running Tests](#running-tests)
  - [Coverage by Component](#coverage-by-component)
- [Code Quality](#code-quality)
- [Contributing](#contributing)

## Development Setup

### Using Docker (Recommended)

The project includes Docker Compose configuration for easy development setup.

```bash
# Start the container
make up

# Install dependencies
make install

# Run tests
make test

# Run tests with coverage
make test-coverage

# Run all QA checks
make qa
```

### Without Docker

If you prefer to run everything locally:

```bash
composer install
composer test
composer test-coverage
composer qa
```

## Testing

The bundle includes comprehensive tests. All tests are located in the `tests/` directory.

### Test Statistics

Run `composer test` or `make test-coverage` for current test and coverage statistics. See the [README](../README.md) "Current Status" section for the latest numbers. The test suite includes unit and integration tests for fakers, services, commands, events, and attributes.

### Running Tests

```bash
# Run all tests
composer test

# Run tests with coverage report
composer test-coverage

# View coverage report
open coverage/index.html
```

### Coverage by Component

- **Fakers**, **services**, **commands**, and **attributes** are covered by the PHPUnit suite; see `make test-coverage` for current line coverage (target: 100% on `src/`).

## Code Quality

The bundle uses PHP-CS-Fixer to enforce code style (PSR-12).

```bash
# Check code style
composer cs-check

# Fix code style
composer cs-fix
```

## Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details on how to contribute to this project.

For information about our Git workflow and branching strategy, see [BRANCHING.md](BRANCHING.md).
