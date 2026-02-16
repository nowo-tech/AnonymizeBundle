# Makefile for Anonymize Bundle Demos
# Manages all Symfony demos (6, 7, 8)

.PHONY: help all up down install setup clean logs status

# Default target
help:
	@echo "Anonymize Bundle Demos - Development Commands"
	@echo ""
	@echo "Usage: make <target>"
	@echo ""
	@echo "Targets:"
	@echo "  all           Complete setup: up + install + setup (recommended)"
	@echo "  up            Start all demo containers"
	@echo "  down          Stop all demo containers"
	@echo "  install       Install Composer dependencies for all demos"
	@echo "  setup         Setup databases and load fixtures for all demos"
	@echo "  clean         Remove vendor, var, and cache from all demos"
	@echo "  logs          Show container logs"
	@echo "  status        Show status of all containers"
	@echo ""
	@echo "Individual demo targets:"
	@echo "  up-symfony6   Start Symfony 6 demo"
	@echo "  up-symfony7   Start Symfony 7 demo"
	@echo "  up-symfony8   Start Symfony 8 demo"
	@echo "  setup-symfony6 Setup Symfony 6 demo (DB + fixtures)"
	@echo "  setup-symfony7 Setup Symfony 7 demo (DB + fixtures)"
	@echo "  setup-symfony8 Setup Symfony 8 demo (DB + fixtures)"
	@echo ""

# Complete setup: up + install + setup
all:
	@echo "🚀 Complete setup: Starting containers, installing dependencies, and loading data..."
	@$(MAKE) up
	@$(MAKE) install
	@$(MAKE) setup
	@echo ""
	@echo "✅ All demos are ready!"
	@$(MAKE) status

# Start all demos (FrankenPHP: one container per demo, no nginx)
up:
	@echo "🚀 Starting all demo containers (FrankenPHP)..."
	@cd symfony6 && docker-compose up -d --build
	@cd symfony7 && docker-compose up -d --build
	@cd symfony8 && docker-compose up -d --build
	@echo "✅ All demos started. Waiting for services to be ready..."
	@sleep 15
	@echo "✅ Ready!"

# Stop all demos
down:
	@echo "🛑 Stopping all demo containers..."
	@cd symfony6 && docker-compose down
	@cd symfony7 && docker-compose down
	@cd symfony8 && docker-compose down
	@echo "✅ All demos stopped."

# Install dependencies for all demos
install:
	@echo "📦 Installing Composer dependencies for all demos..."
	@cd symfony6 && docker-compose exec -T php composer install --no-interaction
	@cd symfony7 && docker-compose exec -T php composer install --no-interaction
	@cd symfony8 && docker-compose exec -T php composer install --no-interaction
	@echo "✅ Dependencies installed."

# Setup databases and load fixtures for all demos
setup:
	@echo "🗄️  Setting up databases and loading fixtures for all demos..."
	@$(MAKE) setup-symfony6
	@$(MAKE) setup-symfony7
	@$(MAKE) setup-symfony8
	@echo "✅ All demos setup complete."

# Setup Symfony 6 demo
setup-symfony6:
	@echo "📊 Setting up Symfony 6 demo..."
	@cd symfony6 && docker-compose exec -T php php bin/console doctrine:database:create --if-not-exists --connection=default 2>&1 | grep -v "already exists" || true
	@cd symfony6 && docker-compose exec -T php php bin/console doctrine:database:create --if-not-exists --connection=postgres 2>&1 | grep -v "already exists" || true
	@cd symfony6 && docker-compose exec -T php php bin/console doctrine:schema:update --force --em=default 2>&1 | tail -2
	@cd symfony6 && docker-compose exec -T php php bin/console doctrine:schema:update --force --em=postgres 2>&1 | tail -2
	@cd symfony6 && docker-compose exec -T php php bin/console doctrine:fixtures:load --no-interaction --em=default 2>&1 | tail -3
	@cd symfony6 && docker-compose exec -T php php bin/console doctrine:fixtures:load --no-interaction --em=postgres 2>&1 | tail -3
	@echo "✅ Symfony 6 setup complete."

# Setup Symfony 7 demo
setup-symfony7:
	@echo "📊 Setting up Symfony 7 demo..."
	@cd symfony7 && docker-compose exec -T php php bin/console doctrine:database:create --if-not-exists --connection=default 2>&1 | grep -v "already exists" || true
	@cd symfony7 && docker-compose exec -T php php bin/console doctrine:database:create --if-not-exists --connection=postgres 2>&1 | grep -v "already exists" || true
	@cd symfony7 && docker-compose exec -T php php bin/console doctrine:schema:update --force --em=default 2>&1 | tail -2
	@cd symfony7 && docker-compose exec -T php php bin/console doctrine:schema:update --force --em=postgres 2>&1 | tail -2
	@cd symfony7 && docker-compose exec -T php php bin/console doctrine:fixtures:load --no-interaction --em=default 2>&1 | tail -3
	@cd symfony7 && docker-compose exec -T php php bin/console doctrine:fixtures:load --no-interaction --em=postgres 2>&1 | tail -3
	@echo "✅ Symfony 7 setup complete."

# Setup Symfony 8 demo
setup-symfony8:
	@echo "📊 Setting up Symfony 8 demo..."
	@cd symfony8 && docker-compose exec -T php php bin/console doctrine:database:create --if-not-exists --connection=default 2>&1 | grep -v "already exists" || true
	@cd symfony8 && docker-compose exec -T php php bin/console doctrine:database:create --if-not-exists --connection=postgres 2>&1 | grep -v "already exists" || true
	@cd symfony8 && docker-compose exec -T php php bin/console doctrine:schema:update --force --em=default 2>&1 | tail -2
	@cd symfony8 && docker-compose exec -T php php bin/console doctrine:schema:update --force --em=postgres 2>&1 | tail -2
	@cd symfony8 && docker-compose exec -T php php bin/console doctrine:fixtures:load --no-interaction --em=default 2>&1 | tail -3
	@cd symfony8 && docker-compose exec -T php php bin/console doctrine:fixtures:load --no-interaction --em=postgres 2>&1 | tail -3
	@echo "✅ Symfony 8 setup complete."

# Start individual demos
up-symfony6:
	@echo "🚀 Starting Symfony 6 demo..."
	@cd symfony6 && docker-compose up -d --build
	@sleep 10

up-symfony7:
	@echo "🚀 Starting Symfony 7 demo..."
	@cd symfony7 && docker-compose up -d --build
	@sleep 10

up-symfony8:
	@echo "🚀 Starting Symfony 8 demo..."
	@cd symfony8 && docker-compose up -d --build
	@sleep 10

# Clean all demos
clean:
	@echo "🧹 Cleaning all demos..."
	@cd symfony6 && rm -rf vendor var/cache var/log
	@cd symfony7 && rm -rf vendor var/cache var/log
	@cd symfony8 && rm -rf vendor var/cache var/log
	@echo "✅ Clean complete."

# Show logs
logs:
	@echo "📋 Showing logs for all demos..."
	@docker-compose -f symfony6/docker-compose.yml -f symfony7/docker-compose.yml -f symfony8/docker-compose.yml logs --tail=50

# Show status
status:
	@echo "📊 Status of all demo containers:"
	@docker ps --filter "name=anonymize-demo" --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" | head -20
	@echo ""
	@echo "🌐 Web Access:"
	@echo "  Symfony 6: http://localhost:8001"
	@echo "  Symfony 7: http://localhost:8000"
	@echo "  Symfony 8: http://localhost:8002"
	@echo ""
	@echo "🗄️  phpMyAdmin (MySQL):"
	@echo "  Symfony 6: http://localhost:8082"
	@echo "  Symfony 7: http://localhost:8080"
	@echo "  Symfony 8: http://localhost:8084"
	@echo ""
	@echo "🗄️  pgAdmin (PostgreSQL):"
	@echo "  Symfony 6: http://localhost:8083"
	@echo "  Symfony 7: http://localhost:8081"
	@echo "  Symfony 8: http://localhost:8085"
