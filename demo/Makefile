# Makefile for Anonymize Bundle Demos
# Manages Symfony 7 and 8 demos

DEMOS := symfony7 symfony8

.PHONY: help all up down install setup clean logs status update-bundle-symfony7 update-bundle-symfony8 update-bundle-all update-deps-all release-check release-verify

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
	@echo "  up-symfony7   Start Symfony 7 demo"
	@echo "  up-symfony8   Start Symfony 8 demo"
	@echo "  restart-symfony7  Restart Symfony 7 demo containers"
	@echo "  restart-symfony8  Restart Symfony 8 demo containers"
	@echo "  build-symfony7    Rebuild Symfony 7 demo image (no cache)"
	@echo "  build-symfony8    Rebuild Symfony 8 demo image (no cache)"
	@echo "  setup-symfony7 Setup Symfony 7 demo (DB + fixtures)"
	@echo "  setup-symfony8 Setup Symfony 8 demo (DB + fixtures)"
	@echo "  update-bundle-symfony7  Update bundle from path repo (Symfony 7)"
	@echo "  update-bundle-symfony8  Update bundle from path repo (Symfony 8)"
	@echo "  update-deps-all       Run composer update in all demo containers"
	@echo "  release-check   Pre-release: test-coverage-all, release-verify"
	@echo "  release-verify  Start each demo, healthcheck (HTTP 200), then stop"
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
	@cd symfony7 && docker-compose up -d --build
	@cd symfony8 && docker-compose up -d --build
	@echo "✅ All demos started. Waiting for services to be ready..."
	@sleep 15
	@echo "Access:"
	@for d in symfony7 symfony8; do \
	  echo -n "  $$d: "; \
	  (cd $$d && PORT=$$(grep "^PORT=" .env 2>/dev/null | cut -d= -f2 | tr -d '\r'); \
	    [ -z "$$PORT" ] && PORT=$$(grep "^PORT=" .env.example 2>/dev/null | cut -d= -f2 | tr -d '\r'); \
	    echo "Demo started at: http://localhost:$$PORT"); \
	done

# Stop all demos
down:
	@echo "🛑 Stopping all demo containers..."
	@cd symfony7 && docker-compose down
	@cd symfony8 && docker-compose down
	@echo "✅ All demos stopped."

down-symfony7:
	@cd symfony7 && docker-compose down

down-symfony8:
	@cd symfony8 && docker-compose down

# Install dependencies for all demos
install:
	@echo "📦 Installing Composer dependencies for all demos..."
	@cd symfony7 && docker-compose exec -T php composer install --no-interaction
	@cd symfony8 && docker-compose exec -T php composer install --no-interaction
	@echo "✅ Dependencies installed."

# Setup databases and load fixtures for all demos
setup:
	@echo "🗄️  Setting up databases and loading fixtures for all demos..."
	@$(MAKE) setup-symfony7
	@$(MAKE) setup-symfony8
	@echo "✅ All demos setup complete."

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
up-symfony7:
	@echo "🚀 Starting Symfony 7 demo..."
	@cd symfony7 && docker-compose up -d --build
	@sleep 10

up-symfony8:
	@echo "🚀 Starting Symfony 8 demo..."
	@cd symfony8 && docker-compose up -d --build
	@sleep 10

restart-symfony7:
	@cd symfony7 && docker-compose restart

restart-symfony8:
	@cd symfony8 && docker-compose restart

build-symfony7:
	@cd symfony7 && docker-compose build --no-cache

build-symfony8:
	@cd symfony8 && docker-compose build --no-cache

update-bundle-symfony7:
	@$(MAKE) -C symfony7 update-bundle

update-bundle-symfony8:
	@$(MAKE) -C symfony8 update-bundle

# Run update-bundle in each demo before tests (REQ-DEMO-007)
update-bundle-all: update-bundle-symfony7 update-bundle-symfony8

# Clean all demos
clean:
	@echo "🧹 Cleaning all demos..."
	@cd symfony7 && rm -rf vendor var/cache var/log
	@cd symfony8 && rm -rf vendor var/cache var/log
	@echo "✅ Clean complete."

# Show logs
logs:
	@echo "📋 Showing logs for all demos..."
	@docker-compose -f symfony7/docker-compose.yml -f symfony8/docker-compose.yml logs --tail=50

# release-check: update-bundle in each demo, then test-coverage-all, then release-verify
test-coverage-all:
	@for demo in symfony7 symfony8; do $(MAKE) -C $$demo test-coverage || exit 1; done
	@echo "✅ test-coverage-all done."
release-check: update-bundle-all test-coverage-all release-verify
	@echo "✅ All demos passed release-check."

# Release verify: for each demo, up → sleep → healthcheck (HTTP 200) → down
release-verify:
	@for demo in symfony7 symfony8; do \
		port=$$(case $$demo in symfony7) echo 8000;; symfony8) echo 8002;; *) echo 8000;; esac); \
		echo "🔍 Release verify: $$demo (port $$port)..."; \
		$(MAKE) up-$$demo; \
		echo "⏳ Waiting for $$demo to be ready..."; \
		sleep 15; \
		code=$$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:$$port 2>/dev/null || echo "000"); \
		$(MAKE) down-$$demo 2>/dev/null || true; \
		if [ "$$code" != "200" ]; then echo "❌ $$demo healthcheck failed (HTTP $$code)"; exit 1; fi; \
		echo "✅ $$demo OK (HTTP $$code)"; \
	done
	@echo "✅ All demos passed release-verify."

# Show status
status:
	@echo "📊 Status of all demo containers:"
	@docker ps --filter "name=anonymize-demo" --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" | head -20
	@echo ""
	@echo "🌐 Web Access:"
	@echo "  Symfony 7: http://localhost:8000"
	@echo "  Symfony 8: http://localhost:8002"
	@echo ""
	@echo "🗄️  phpMyAdmin (MySQL):"
	@echo "  Symfony 7: http://localhost:8080"
	@echo "  Symfony 8: http://localhost:8084"
	@echo ""
	@echo "🗄️  pgAdmin (PostgreSQL):"
	@echo "  Symfony 7: http://localhost:8081"
	@echo "  Symfony 8: http://localhost:8085"

# REQ-MAKE-008: update-deps-all
BUNDLE_ROOT := $(abspath $(CURDIR)/..)
include $(abspath $(CURDIR)/../../.scripts/Makefile.demo-aggregate-update-deps.mk)
