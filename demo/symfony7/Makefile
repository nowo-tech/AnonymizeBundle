# Makefile for Anonymize Bundle Demo - Symfony 7
# Simplifies Docker commands for the demo

.PHONY: help up down restart build shell install clean cache-clear setup update-bundle ensure-up test test-coverage

# Default target
help:
	@echo "Anonymize Bundle Demo - Symfony 7 - Development Commands"
	@echo ""
	@echo "Usage: make <target>"
	@echo ""
	@echo "Targets:"
	@echo "  up             Start Docker containers (PHP, MySQL, PostgreSQL)"
	@echo "  down           Stop Docker containers"
	@echo "  restart        Restart containers (docker compose restart)"
	@echo "  build          Rebuild image (no cache)"
	@echo "  shell          Open shell in PHP container"
	@echo "  install        Install Composer dependencies"
	@echo "  setup          Setup databases and load fixtures"
	@echo "  update-bundle  Update anonymize-bundle from repo"
	@echo "  test           Run tests"
	@echo "  test-coverage  Run tests with coverage (console output)"
	@echo "  clean          Remove vendor, var, and cache"
	@echo "  cache-clear    Full cache clear (fixes missing Container* service files)"
	@echo "  logs           Show container logs"
	@echo ""
	@echo "Anonymization:"
	@echo "  anonymize-dry-run    Test anonymization (dry-run)"
	@echo "  anonymize            Run anonymization"
	@echo "  anonymize-stats      Run with statistics export"
	@echo ""
	@echo "Database:"
	@echo "  db-create     Create databases"
	@echo "  db-drop       Drop databases"
	@echo "  db-reset      Reset databases (drop + create + schema + fixtures)"
	@echo "  db-fixtures   Load fixtures"
	@echo "  db-view       View current database records"
	@echo ""
	@echo "Web Server:"
	@echo "  server        Start Symfony web server (http://localhost:8000)"
	@echo ""

# Build and start containers
up:
	@echo "🚀 Starting demo containers..."
	@if [ ! -f .env ]; then \
		echo "⚠️  .env file not found. Creating from defaults..."; \
		echo "APP_ENV=dev" > .env; \
		echo "APP_SECRET=your-secret-key-change-this-in-production" >> .env; \
		echo "DATABASE_URL=mysql://demo_user:password@mysql:3306/anonymize_demo?serverVersion=8.0&charset=utf8mb4" >> .env; \
		echo "DATABASE_URL_POSTGRES=postgresql://demo_user:password@postgres:5432/anonymize_demo?serverVersion=16&charset=utf8" >> .env; \
		echo "MYSQL_ROOT_PASSWORD=password" >> .env; \
		echo "MYSQL_DATABASE=anonymize_demo" >> .env; \
		echo "MYSQL_USER=demo_user" >> .env; \
		echo "MYSQL_PASSWORD=password" >> .env; \
		echo "POSTGRES_USER=demo_user" >> .env; \
		echo "POSTGRES_PASSWORD=password" >> .env; \
		echo "POSTGRES_DB=anonymize_demo" >> .env; \
		echo "✅ .env file created"; \
	fi
	docker-compose up -d --build
	@echo "⏳ Waiting for MySQL and PostgreSQL to be ready..."
	@echo "Waiting for container to be ready..."
	@sleep 5
	@echo "Installing dependencies..."
	@docker-compose exec -T -e COMPOSER_MEMORY_LIMIT=-1 php composer install --no-interaction
	@echo "Creating databases, schema and loading fixtures..."
	@$(MAKE) setup
	@echo ""
	@echo "  make update-bundle - Update the bundle from repo"

# Stop containers
down:
	@echo "🛑 Stopping containers..."
	docker-compose down
	@echo "✅ Containers stopped!"

# Restart containers (no down/up)
restart:
	@echo "🔄 Restarting containers..."
	docker-compose restart
	@echo "✅ Containers restarted!"

# Rebuild image without cache
build:
	@echo "🔨 Rebuilding image (no cache)..."
	docker-compose build --no-cache
	@echo "✅ Image rebuilt!"

# Ensure container is running (start if not). Used by install, shell, setup, update-bundle, etc.
ensure-up:
	@if ! docker-compose exec -T php true 2>/dev/null; then \
		echo "Starting container..."; \
		docker-compose up -d; \
		sleep 5; \
	fi

# Open shell in PHP container
shell: ensure-up
	docker-compose exec php sh

# Install dependencies
install: ensure-up
	@echo "📦 Installing dependencies..."
	docker-compose exec -T -e COMPOSER_MEMORY_LIMIT=-1 php composer install --no-interaction
	@echo "✅ Dependencies installed!"
	@echo ""
	@echo "You can now run:"
	@echo "  make anonymize-dry-run    - Test anonymization (dry-run)"
	@echo "  make anonymize            - Run anonymization"

# Update the bundle from the path repo (use if you get "Unknown named parameter $truncate")
update-bundle: ensure-up
	@echo "📦 Updating nowo-tech/anonymize-bundle from repo..."
	@docker-compose exec -T php git config --global --add safe.directory /bundles 2>/dev/null || true
	docker-compose exec -T php composer update nowo-tech/anonymize-bundle --with-all-dependencies --no-interaction
	docker-compose exec -T php php bin/console cache:clear
	@echo "✅ Bundle updated! Run: make anonymize-dry-run"

test: ensure-up
	docker-compose exec -T php composer test 2>/dev/null || docker-compose exec -T php php bin/console list

test-coverage: ensure-up
	docker-compose exec -T php composer test-coverage 2>/dev/null || docker-compose exec -T php composer test

# Complete setup
setup: ensure-up
	@echo "🔧 Setting up demo (databases and fixtures)..."
	@echo "Waiting for MySQL and PostgreSQL to be ready..."
	@sleep 3
	@echo "Installing base dependencies (without bundle)..."
	-docker-compose exec -T -e COMPOSER_MEMORY_LIMIT=-1 php composer install --no-interaction --ignore-platform-reqs 2>/dev/null || docker-compose exec -T -e COMPOSER_MEMORY_LIMIT=-1 php composer install --no-interaction
	@echo "Creating databases..."
	@echo "  - MySQL (default connection)..."
	-docker-compose exec -T php php bin/console doctrine:database:create --if-not-exists --no-interaction --connection=default
	@echo "  - PostgreSQL (postgres connection)..."
	-docker-compose exec -T php php bin/console doctrine:database:create --if-not-exists --no-interaction --connection=postgres
	@echo "Creating schemas..."
	@echo "  - MySQL schema..."
	-docker-compose exec -T php php bin/console doctrine:schema:create --no-interaction --em=default
	@echo "  - PostgreSQL schema..."
	-docker-compose exec -T php php bin/console doctrine:schema:create --no-interaction --em=postgres
	@echo "Loading fixtures..."
	@echo "  - MySQL fixtures..."
	-docker-compose exec -T php php bin/console doctrine:fixtures:load --no-interaction --em=default
	@echo "  - PostgreSQL fixtures..."
	-docker-compose exec -T php php bin/console doctrine:fixtures:load --no-interaction --em=postgres
	@echo "✅ Demo setup complete!"
	@echo ""
	@echo "Then you can run:"
	@echo "  make anonymize-dry-run    - Test anonymization (dry-run)"
	@echo "  make anonymize            - Run anonymization"
	@echo "  make anonymize-stats      - Run with statistics"

# Anonymization commands
anonymize-dry-run:
	@echo "🔍 Running anonymization in dry-run mode..."
	docker-compose exec -T php php bin/console nowo:anonymize:run --dry-run

anonymize:
	@echo "🔄 Running anonymization..."
	docker-compose exec -T php php bin/console nowo:anonymize:run

anonymize-stats:
	@echo "📊 Running anonymization with statistics..."
	docker-compose exec -T php php bin/console nowo:anonymize:run --stats-json stats.json

# Database commands
db-create:
	@echo "📦 Creating databases..."
	@echo "  - MySQL..."
	-docker-compose exec -T php php bin/console doctrine:database:create --if-not-exists --no-interaction --connection=default
	@echo "  - PostgreSQL..."
	-docker-compose exec -T php php bin/console doctrine:database:create --if-not-exists --no-interaction --connection=postgres

db-drop:
	@echo "🗑️  Dropping databases..."
	-docker-compose exec -T php php bin/console doctrine:database:drop --force --if-exists --no-interaction --connection=default
	-docker-compose exec -T php php bin/console doctrine:database:drop --force --if-exists --no-interaction --connection=postgres

db-reset: db-drop db-create
	@echo "🔄 Resetting database schemas..."
	@echo "  - MySQL..."
	-docker-compose exec -T php php bin/console doctrine:schema:create --no-interaction --em=default
	@echo "  - PostgreSQL..."
	-docker-compose exec -T php php bin/console doctrine:schema:create --no-interaction --em=postgres
	@echo "📥 Loading fixtures..."
	@echo "  - MySQL fixtures..."
	-docker-compose exec -T php php bin/console doctrine:fixtures:load --no-interaction --em=default
	@echo "  - PostgreSQL fixtures..."
	-docker-compose exec -T php php bin/console doctrine:fixtures:load --no-interaction --em=postgres
	@echo "✅ Database reset complete!"

db-fixtures:
	@echo "📥 Loading fixtures..."
	@echo "  - MySQL fixtures..."
	-docker-compose exec -T php php bin/console doctrine:fixtures:load --no-interaction --em=default
	@echo "  - PostgreSQL fixtures..."
	-docker-compose exec -T php php bin/console doctrine:fixtures:load --no-interaction --em=postgres
	@echo "✅ Fixtures loaded!"

db-view:
	@echo "📋 Viewing database records..."
	@echo ""
	@echo "=== MySQL (default connection) ==="
	@echo "Users:"
	docker-compose exec -T php php bin/console dbal:run-sql "SELECT * FROM users" --connection=default 2>/dev/null || echo "No users table or connection error"
	@echo ""
	@echo "Customers:"
	docker-compose exec -T php php bin/console dbal:run-sql "SELECT * FROM customers" --connection=default 2>/dev/null || echo "No customers table or connection error"
	@echo ""
	@echo "=== PostgreSQL (postgres connection) ==="
	@echo "Users:"
	docker-compose exec -T php php bin/console dbal:run-sql "SELECT * FROM users" --connection=postgres 2>/dev/null || echo "No users table or connection error"
	@echo ""
	@echo "Customers:"
	docker-compose exec -T php php bin/console dbal:run-sql "SELECT * FROM customers" --connection=postgres 2>/dev/null || echo "No customers table or connection error"

# Show logs
logs:
	docker-compose logs -f

# Start web server (nginx is already running in docker-compose)
server:
	@echo "🌐 Web server (nginx) is already running!"
	@echo "Access the application at: http://localhost:8000"

# Full cache clear (regenerates container; use if you see "Failed to open stream ... get*Service.php")
cache-clear:
	@echo "🗑️  Clearing cache..."
	docker-compose exec -T php rm -rf var/cache/*
	docker-compose exec -T php php bin/console cache:clear
	@echo "✅ Cache cleared!"

# Clean vendor and cache
clean:
	@echo "🧹 Cleaning vendor, var, and cache..."
	rm -rf vendor
	rm -rf var/cache/*
	rm -rf var/log/*
	@echo "✅ Cleaned!"
