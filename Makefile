# Makefile for Anonymize Bundle
# Simplifies Docker commands for development

COMPOSE_FILE := docker-compose.yml
# Prefer Compose V2 plugin (GitHub Actions / modern Docker Desktop); fall back to docker-compose V1 (REQ-MAKE-010).
COMPOSE_BIN := $(shell docker compose version >/dev/null 2>&1 && echo "docker compose" || echo "docker-compose")
COMPOSE     := $(COMPOSE_BIN) -f $(COMPOSE_FILE)
SERVICE_PHP := php

.PHONY: help up down down-dev build shell install test test-coverage coverage-php-percent cs-check cs-fix qa clean setup-hooks test-up test-down test-shell ensure-up assets release-check release-check-demos composer-sync rector rector-dry phpstan update update-deps update-deps-demos validate check-no-cursor-coauthor check-open-prs strip-cursor-coauthor-from-history demo-smoke

# Default target
help:
	@echo "Anonymize Bundle - Development Commands"
	@echo ""
	@echo "Usage: make <target>"
	@echo ""
	@echo "Targets:"
	@echo "  up            Start Docker container"
	@echo "  down          Stop Docker container"
	@echo "  down-dev      Stop containers (keep volumes; REQ-MAKE-007)"
	@echo "  build         Rebuild Docker image (no cache)"
	@echo "  shell         Open shell in container"
	@echo "  install       Install Composer dependencies"
	@echo "  assets        No frontend assets in this bundle (no-op)"
	@echo "  test          Run PHPUnit tests (unit tests only)"
	@echo "  test-coverage Run tests with code coverage (unit tests only)"
	@echo "  demo-smoke    Boot demo/symfony8 and assert HTTP 200 (REQ-TEST-011)"
	@echo "  coverage-php-percent Print global PHP Lines % from coverage-php.txt (after test-coverage)"
	@echo "  cs-check      Check code style"
	@echo "  cs-fix        Fix code style"
	@echo "  rector        Apply Rector refactoring"
	@echo "  rector-dry    Run Rector in dry-run mode"
	@echo "  phpstan       Run PHPStan static analysis"
	@echo "  qa            Run all QA checks (cs-check + test)"
	@echo "  release-check Pre-release: ensure-up, git/PR gates, cs-fix, cs-check, rector-dry, phpstan, test-coverage, demo healthchecks"
	@echo "  check-open-prs Fail if unresolved open GitHub PRs remain (REQ-REL-003)"
	@echo "  composer-sync Validate composer.json and align composer.lock (no install)"
	@echo "  clean         Remove vendor and cache"
	@echo "  update        Update composer.lock (composer update, bundle only)"
	@echo "  update-deps   Update composer in bundle container and all demos (REQ-MAKE-008)"
	@echo "  validate      Run composer validate --strict"
	@echo ""
	@echo "Bundle-specific:"
	@echo "  test-up       Start test container with databases"
	@echo "  test-down     Stop test container"
	@echo "  test-shell    Open shell in test container"
	@echo "  test-with-db  Run tests with databases (integration tests)"
	@echo "  test-coverage-with-db Run tests with coverage and databases"
	@echo "  setup-hooks   Install git pre-commit hooks"
	@echo ""
	@echo "Demos:"
	@echo "  demo-smoke    Boot FrankenPHP demo and curl HTTP 200 (REQ-TEST-011)"
	@echo "  (use make -C demo or make -C demo/symfonyX)"
	@echo ""

# Rebuild Docker image (no cache)
build:
	$(COMPOSE) build --no-cache

# Build and start containers (php + mysql + postgres)
up:
	@echo "Building Docker image..."
	$(COMPOSE) build
	@echo "Starting containers (PHP, MySQL, PostgreSQL)..."
	$(COMPOSE) up -d
	@echo "Waiting for databases to be ready..."
	@sleep 10
	@echo "Installing dependencies..."
	$(COMPOSE) exec -T php composer install --no-interaction
	@echo "✅ Containers ready!"

# Stop container (root $(COMPOSE))
down:
	$(COMPOSE) down

# Stop containers without removing volumes (REQ-MAKE-007)
down-dev:
	$(COMPOSE) down --remove-orphans

# Ensure root container is running (start if not). Used by cs-fix, cs-check, qa, install, test, test-coverage.
ensure-up:
	@if ! $(COMPOSE) exec -T php true 2>/dev/null; then \
		echo "Starting container ($(COMPOSE): php + mysql + postgres)..."; \
		$(COMPOSE) up -d; \
		sleep 10; \
		$(COMPOSE) exec -T php composer install --no-interaction; \
	fi

# Open shell in container (root $(COMPOSE))
shell:
	$(COMPOSE) exec php sh

# Install dependencies (runs inside root $(COMPOSE) php container)
install: ensure-up
	$(COMPOSE) exec -T php composer install

# Run tests (runs inside root $(COMPOSE) php container)
# Run tests (no -T so TTY is allocated and PHPUnit can show colors in console)
test: ensure-up
	$(COMPOSE) exec php composer test

# Run tests with coverage (runs inside root $(COMPOSE) php container)
# Run tests with coverage (no -T so coverage is shown in console with colors)
test-coverage: ensure-up
	$(COMPOSE) exec php composer test-coverage | tee coverage-php.txt
	/bin/sh "$(CURDIR)/.scripts/php-coverage-percent.sh" coverage-php.txt

# Print global PHP line coverage % from coverage-php.txt (run test-coverage first to regenerate the file)
coverage-php-percent:
	/bin/sh "$(CURDIR)/.scripts/php-coverage-percent.sh" coverage-php.txt

# Run tests with databases (integration tests; same compose: php + mysql + postgres)
test-with-db: ensure-up
	$(COMPOSE) exec -T php composer test

# Run tests with coverage and databases
test-coverage-with-db: ensure-up
	$(COMPOSE) exec php composer test-coverage

# No frontend assets in this bundle
assets:
	@echo "No frontend assets in this bundle."

# Start containers (php + mysql + postgres)
test-up:
	@echo "Building Docker image..."
	$(COMPOSE) build
	@echo "Starting containers (PHP, MySQL, PostgreSQL)..."
	$(COMPOSE) up -d
	@echo "Waiting for databases to be ready..."
	@sleep 10
	@echo "Installing dependencies..."
	$(COMPOSE) exec -T php composer install --no-interaction
	@echo "✅ Containers ready!"

# Stop containers
test-down:
	$(COMPOSE) down

# Open shell in php container
test-shell:
	$(COMPOSE) exec php sh

# Check code style (runs inside root $(COMPOSE) php container)
cs-check: ensure-up
	$(COMPOSE) exec -T php composer cs-check

# Fix code style (runs inside root $(COMPOSE) php container)
cs-fix: ensure-up
	$(COMPOSE) exec -T php composer cs-fix

# Run Rector (apply refactoring)
rector: ensure-up
	$(COMPOSE) exec -T php composer rector

# Run Rector in dry-run mode
rector-dry: ensure-up
	$(COMPOSE) exec -T php composer rector-dry

# Run PHPStan static analysis
phpstan: ensure-up
	$(COMPOSE) exec -T php composer phpstan

# Validate composer.json and align composer.lock (generate/update lock without install)
composer-sync: ensure-up
	$(COMPOSE) exec -T php composer validate --strict
	$(COMPOSE) exec -T php composer update --no-install

# Update composer.lock
update: ensure-up
	$(COMPOSE) exec -T php composer update --no-interaction

# Validate composer.json
validate: ensure-up
	$(COMPOSE) exec -T php composer validate --strict

# Run all QA (runs inside root $(COMPOSE) php container)
qa: ensure-up
	$(COMPOSE) exec -T php composer qa

# Pre-release (REQ-MAKE-002): ensure-up → git/PR gates → composer-sync → QA → demos
release-check: ensure-up check-no-cursor-coauthor check-open-prs composer-sync cs-fix cs-check rector-dry phpstan test-coverage release-check-demos

# REQ-TEST-011 — boot demo stack and assert one HTTP 200
demo-smoke:
	@$(MAKE) -C demo/symfony8 up
	@PORT=$$(grep "^PORT=" demo/symfony8/.env 2>/dev/null | cut -d= -f2 | tr -d '\r'); \
	[ -z "$$PORT" ] && PORT=$$(grep "^PORT=" demo/symfony8/.env.example 2>/dev/null | cut -d= -f2 | tr -d '\r'); \
	[ -z "$$PORT" ] && PORT=8002; \
	echo "Smoke GET http://localhost:$$PORT/"; \
	ok=0; \
	for i in 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15 16 17 18 19 20 21 22 23 24 25 26 27 28 29 30; do \
		code=$$(curl -fsS -o /dev/null -w "%{http_code}" "http://localhost:$$PORT/" 2>/dev/null || true); \
		if [ "$$code" = "200" ]; then echo "demo-smoke OK (HTTP 200)"; ok=1; break; fi; \
		echo "  attempt $$i: HTTP $${code:-000}, retrying..."; \
		sleep 2; \
	done; \
	if [ "$$ok" != "1" ]; then echo "demo-smoke failed: no HTTP 200 after retries"; exit 1; fi

release-check-demos:
	@$(MAKE) -C demo release-check

# Clean vendor and cache
clean:
	rm -rf vendor
	rm -rf .phpunit.cache
	rm -rf coverage
	rm -f coverage.xml
	rm -f .php-cs-fixer.cache

# Setup git hooks for pre-commit checks
check-no-cursor-coauthor:
	@chmod +x .scripts/check-no-cursor-coauthor.sh
	@./.scripts/check-no-cursor-coauthor.sh HEAD

# Fail when open GitHub PRs are unresolved (REQ-REL-003)
check-open-prs:
	@chmod +x .scripts/check-open-prs.sh
	@bash .scripts/check-open-prs.sh

strip-cursor-coauthor-from-history:
	@chmod +x .scripts/strip-cursor-coauthor-from-history.sh
	@./.scripts/strip-cursor-coauthor-from-history.sh main

setup-hooks:
	@chmod +x .githooks/pre-commit 2>/dev/null || true
	@chmod +x .githooks/commit-msg 2>/dev/null || true
	@git config core.hooksPath .githooks
	@echo "✅ Git hooks installed (.githooks — pre-commit + commit-msg for REQ-GIT-001)."


# REQ-MAKE-008: update-deps (REQ-MAKE-008)
BUNDLE_ROOT := $(abspath $(dir $(lastword $(MAKEFILE_LIST))))
# Optional: monorepo helper absent on standalone GitHub Actions checkout (REQ-MAKE-009).
-include $(BUNDLE_ROOT)/../.scripts/Makefile.update-deps.mk
