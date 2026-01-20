# Development Guide

This guide covers development setup, testing, and code quality for the Anonymize Bundle.

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

- **Total Tests**: 216 tests
- **Total Assertions**: 512 assertions
- **Code Coverage**: 45.80% line coverage (414/904 lines)
- **Class Coverage**: 52.78% (19/36 classes)
- **Method Coverage**: 62.37% (58/93 methods)

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

- **Fakers**: Excellent coverage (~98% average, most fakers at 100%)
- **Services**: Good coverage (88-96% for main services)
- **Commands**: Integration tests required (not unit tested)
- **Attributes**: No tests needed (definition-only classes)

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
