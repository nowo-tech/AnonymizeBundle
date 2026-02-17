# Makefile for Anonymize Bundle
# Simplifies Docker commands for development

.PHONY: help up down shell install test test-coverage cs-check cs-fix qa clean setup-hooks test-up test-down test-shell ensure-up

# Default target
help:
	@echo "Anonymize Bundle - Development Commands"
	@echo ""
	@echo "Usage: make <target>"
	@echo ""
	@echo "Targets:"
	@echo "  up            Start Docker container"
	@echo "  down          Stop Docker container"
	@echo "  shell         Open shell in container"
	@echo "  install       Install Composer dependencies"
	@echo "  test          Run PHPUnit tests (unit tests only)"
	@echo "  test-coverage Run tests with code coverage (unit tests only)"
	@echo "  test-with-db  Run tests with databases (integration tests)"
	@echo "  test-coverage-with-db Run tests with coverage and databases"
	@echo "  test-up       Start test container with databases"
	@echo "  test-down     Stop test container"
	@echo "  test-shell    Open shell in test container"
	@echo "  cs-check      Check code style"
	@echo "  cs-fix        Fix code style"
	@echo "  qa            Run all QA checks (cs-check + test)"
	@echo "  clean         Remove vendor and cache"
	@echo "  setup-hooks   Install git pre-commit hooks"
	@echo ""

# Build and start container (root docker-compose)
up:
	@echo "Building Docker image..."
	docker-compose -f docker-compose.yml build
	@echo "Starting container..."
	docker-compose -f docker-compose.yml up -d
	@echo "Waiting for container to be ready..."
	@sleep 2
	@echo "Installing dependencies..."
	docker-compose -f docker-compose.yml exec -T php composer install --no-interaction
	@echo "✅ Container ready!"

# Stop container (root docker-compose)
down:
	docker-compose -f docker-compose.yml down

# Ensure root container is running (start if not). Used by cs-fix, cs-check, qa, install, test, test-coverage.
ensure-up:
	@if ! docker-compose -f docker-compose.yml exec -T php true 2>/dev/null; then \
		echo "Starting container (root docker-compose)..."; \
		docker-compose -f docker-compose.yml up -d; \
		sleep 3; \
		docker-compose -f docker-compose.yml exec -T php composer install --no-interaction; \
	fi

# Open shell in container (root docker-compose)
shell:
	docker-compose -f docker-compose.yml exec php sh

# Install dependencies (runs inside root docker-compose php container)
install: ensure-up
	docker-compose -f docker-compose.yml exec -T php composer install

# Run tests (runs inside root docker-compose php container)
test: ensure-up
	docker-compose -f docker-compose.yml exec -T php composer test

# Run tests with coverage (runs inside root docker-compose php container)
test-coverage: ensure-up
	docker-compose -f docker-compose.yml exec -T php composer test-coverage

# Run tests in test container (with databases)
test-with-db:
	docker-compose -f docker-compose.test.yml exec -T test composer test

# Run tests with coverage in test container (with databases)
test-coverage-with-db:
	docker-compose -f docker-compose.test.yml exec -T test composer test-coverage

# Start test container
test-up:
	@echo "Building test Docker image..."
	docker-compose -f docker-compose.test.yml build
	@echo "Starting test containers (PHP, MySQL, PostgreSQL)..."
	docker-compose -f docker-compose.test.yml up -d
	@echo "Waiting for databases to be ready..."
	@sleep 10
	@echo "Installing dependencies..."
	docker-compose -f docker-compose.test.yml exec -T test composer install --no-interaction
	@echo "✅ Test containers ready!"

# Stop test container
test-down:
	docker-compose -f docker-compose.test.yml down

# Open shell in test container
test-shell:
	docker-compose -f docker-compose.test.yml exec test sh

# Check code style (runs inside root docker-compose php container)
cs-check: ensure-up
	docker-compose -f docker-compose.yml exec -T php composer cs-check

# Fix code style (runs inside root docker-compose php container)
cs-fix: ensure-up
	docker-compose -f docker-compose.yml exec -T php composer cs-fix

# Run all QA (runs inside root docker-compose php container)
qa: ensure-up
	docker-compose -f docker-compose.yml exec -T php composer qa

# Clean vendor and cache
clean:
	rm -rf vendor
	rm -rf .phpunit.cache
	rm -rf coverage
	rm -f coverage.xml
	rm -f .php-cs-fixer.cache

# Setup git hooks for pre-commit checks
setup-hooks:
	chmod +x .githooks/pre-commit
	git config core.hooksPath .githooks
	@echo "✅ Git hooks installed! CS-check and tests will run before each commit."
