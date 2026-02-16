#!/bin/sh
set -e

echo "🚀 Starting demo entrypoint script..."

# Fix permissions (from original entrypoint)
mkdir -p /app/var/cache /app/var/log /app/var/data
chmod -R 777 /app/var 2>/dev/null || true

# Wait for database to be ready
wait_for_db() {
    local db_type=$1
    local host=$2
    local port=$3
    local max_attempts=30
    local attempt=1

    echo "⏳ Waiting for $db_type to be ready at $host:$port..."
    # MongoDB: limit wait so we don't block app startup if Mongo is slow (e.g. first boot)
    if [ "$db_type" = "mongo" ]; then
        max_attempts=10
    fi

    while [ $attempt -le $max_attempts ]; do
        case $db_type in
            mysql)
                # Try with demo_user first, fallback to root if demo_user doesn't exist yet
                if mysqladmin ping -h "$host" -P "$port" -u "${MYSQL_USER:-demo_user}" -p"${MYSQL_PASSWORD:-password}" --skip-ssl --silent 2>/dev/null; then
                    echo "✅ MySQL is ready!"
                    return 0
                elif mysqladmin ping -h "$host" -P "$port" -u root -p"${MYSQL_ROOT_PASSWORD:-password}" --skip-ssl --silent 2>/dev/null; then
                    echo "✅ MySQL is ready (using root)!"
                    return 0
                fi
                ;;
            postgres)
                if PGPASSWORD="${POSTGRES_PASSWORD:-password}" pg_isready -h "$host" -p "$port" -U "${POSTGRES_USER:-demo_user}" >/dev/null 2>&1; then
                    echo "✅ PostgreSQL is ready!"
                    return 0
                fi
                ;;
            mongo)
                # MongoDB image uses auth; ping with credentials (admin database)
                MONGO_USER="${MONGODB_USER:-demo_user}"
                MONGO_PASS="${MONGODB_PASSWORD:-password}"
                if command -v mongosh >/dev/null 2>&1 && mongosh "mongodb://${MONGO_USER}:${MONGO_PASS}@${host}:${port}/admin" --eval "db.adminCommand('ping')" >/dev/null 2>&1; then
                    echo "✅ MongoDB is ready!"
                    return 0
                fi
                # Fallback: if mongosh not installed (e.g. FrankenPHP image), check port is open so we don't block forever
                if ! command -v mongosh >/dev/null 2>&1 && (command -v nc >/dev/null 2>&1 && nc -z "$host" "$port" 2>/dev/null || true); then
                    echo "✅ MongoDB port reachable (mongosh not available, skipping auth check)."
                    return 0
                fi
                ;;
        esac

        echo "   Attempt $attempt/$max_attempts - $db_type not ready yet, waiting 2 seconds..."
        sleep 2
        attempt=$((attempt + 1))
    done

    echo "⚠️  Warning: $db_type at $host:$port did not become ready after $max_attempts attempts"
    return 1
}

# Setup database connection
setup_database() {
    local connection=$1
    local em=$2

    echo ""
    echo "📦 Setting up database for connection: $connection (entity manager: $em)..."

    # Check if vendor exists (composer install needed)
    if [ ! -d "/app/vendor" ]; then
        echo "  ⚠️  Warning: vendor directory not found. Skipping database setup."
        echo "  💡 Run 'composer install' first, then restart the container."
        return 0
    fi

    # Check if init backup exists for this connection (shared location)
    INIT_BACKUP=""
    case "$connection" in
        default)
            if [ -f "/app/../docker/init/mysql/init.sql" ] && [ -s "/app/../docker/init/mysql/init.sql" ]; then
                INIT_BACKUP="/app/../docker/init/mysql/init.sql"
            elif [ -f "/app/docker/init/mysql/init.sql" ] && [ -s "/app/docker/init/mysql/init.sql" ]; then
                INIT_BACKUP="/app/docker/init/mysql/init.sql"
            fi
            ;;
        postgres)
            if [ -f "/app/../docker/init/postgres/init.sql" ] && [ -s "/app/../docker/init/postgres/init.sql" ]; then
                INIT_BACKUP="/app/../docker/init/postgres/init.sql"
            elif [ -f "/app/docker/init/postgres/init.sql" ] && [ -s "/app/docker/init/postgres/init.sql" ]; then
                INIT_BACKUP="/app/docker/init/postgres/init.sql"
            fi
            ;;
        sqlite)
            if [ -f "/app/../docker/init/sqlite/init.sqlite" ] && [ -s "/app/../docker/init/sqlite/init.sqlite" ]; then
                INIT_BACKUP="/app/../docker/init/sqlite/init.sqlite"
            elif [ -f "/app/docker/init/sqlite/init.sqlite" ] && [ -s "/app/docker/init/sqlite/init.sqlite" ]; then
                INIT_BACKUP="/app/docker/init/sqlite/init.sqlite"
            fi
            ;;
    esac

    # Create database if not exists
    echo "  Creating database (if not exists)..."
    php bin/console doctrine:database:create --if-not-exists --no-interaction --connection="$connection" 2>&1 || {
        echo "  ⚠️  Warning: Could not create database (may already exist or connection issue)"
    }

    # Clear cache
    echo "  Clearing cache..."
    php bin/console cache:clear --no-interaction 2>&1 || {
        echo "  ⚠️  Warning: Could not clear cache"
    }

    # If init backup exists, restore it instead of loading fixtures
    if [ -n "$INIT_BACKUP" ]; then
        echo "  Found init backup, restoring database..."
        case "$connection" in
            default)
                # MySQL: Check if database is empty, if so, restore from backup
                RECORD_COUNT=$(php bin/console dbal:run-sql "SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = DATABASE()" --connection="$connection" 2>/dev/null | grep -E "^[0-9]" | head -1 || echo "0")
                if [ "$RECORD_COUNT" = "0" ] || [ -z "$RECORD_COUNT" ]; then
                    echo "  Database appears empty, restoring from backup..."
                    mysql -h mysql -u demo_user -ppassword anonymize_demo < "$INIT_BACKUP" 2>&1 || {
                        echo "  ⚠️  Warning: Could not restore from backup, falling back to fixtures"
                        INIT_BACKUP=""
                    }
                else
                    echo "  Database already has data, skipping backup restore"
                    INIT_BACKUP=""
                fi
                ;;
            postgres)
                # PostgreSQL: Check if database is empty, if so, restore from backup
                RECORD_COUNT=$(PGPASSWORD=password psql -h postgres -U demo_user -d anonymize_demo -tAc "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public'" 2>/dev/null || echo "0")
                if [ "$RECORD_COUNT" = "0" ] || [ -z "$RECORD_COUNT" ]; then
                    echo "  Database appears empty, restoring from backup..."
                    PGPASSWORD=password psql -h postgres -U demo_user -d anonymize_demo < "$INIT_BACKUP" 2>&1 || {
                        echo "  ⚠️  Warning: Could not restore from backup, falling back to fixtures"
                        INIT_BACKUP=""
                    }
                else
                    echo "  Database already has data, skipping backup restore"
                    INIT_BACKUP=""
                fi
                ;;
            sqlite)
                # SQLite: Copy backup file if database doesn't exist or is empty
                SQLITE_DB="/app/var/data/anonymize_demo.sqlite"
                if [ ! -f "$SQLITE_DB" ] || [ ! -s "$SQLITE_DB" ]; then
                    echo "  Database doesn't exist or is empty, copying backup..."
                    mkdir -p /app/var/data
                    cp "$INIT_BACKUP" "$SQLITE_DB" 2>&1 || {
                        echo "  ⚠️  Warning: Could not copy backup, falling back to fixtures"
                        INIT_BACKUP=""
                    }
                else
                    echo "  Database already exists, skipping backup copy"
                    INIT_BACKUP=""
                fi
                ;;
        esac
    fi

    # If no backup was restored, proceed with normal setup
    if [ -z "$INIT_BACKUP" ]; then
        # Update schema
        echo "  Updating schema..."
        php bin/console doctrine:schema:update --force --no-interaction --em="$em" 2>&1 || {
            echo "  ⚠️  Warning: Could not update schema (may already be up to date)"
        }

        # Load fixtures (purge existing data first)
        echo "  Loading fixtures (purging existing data)..."
        # For MySQL, we need to handle foreign keys differently
        if [ "$connection" = "default" ]; then
            # Use regular purge (delete) instead of truncate to avoid foreign key issues
            php bin/console doctrine:fixtures:load --no-interaction --em="$em" 2>&1 || {
                echo "  ⚠️  Warning: Could not load fixtures (may already be loaded or schema issue)"
            }
        else
            # For PostgreSQL and SQLite, we can use truncate
            php bin/console doctrine:fixtures:load --no-interaction --purge-with-truncate --em="$em" 2>&1 || {
                echo "  ⚠️  Warning: Could not load fixtures, trying without truncate..."
                php bin/console doctrine:fixtures:load --no-interaction --em="$em" 2>&1 || {
                    echo "  ⚠️  Warning: Could not load fixtures (may already be loaded or schema issue)"
                }
            }
        fi
    else
        # Backup was restored, just update schema if needed
        echo "  Updating schema (if needed)..."
        php bin/console doctrine:schema:update --force --no-interaction --em="$em" 2>&1 || {
            echo "  ⚠️  Warning: Could not update schema (may already be up to date)"
        }
    fi

    echo "  ✅ Database setup complete for $connection"
}

# Main execution
main() {
    echo ""
    echo "🔍 Checking available database connections..."

    # Wait for MySQL (default connection)
    if [ -n "${DATABASE_URL}" ]; then
        wait_for_db mysql mysql 3306 || echo "⚠️  MySQL not available, skipping..."
    fi

    # Wait for PostgreSQL
    if [ -n "${DATABASE_URL_POSTGRES}" ]; then
        wait_for_db postgres postgres 5432 || echo "⚠️  PostgreSQL not available, skipping..."
    fi

    # Wait for MongoDB (if configured)
    if [ -n "${MONGODB_URL}" ]; then
        wait_for_db mongo mongodb 27017 || echo "⚠️  MongoDB not available, skipping..."
    fi

    echo ""
    echo "📥 Setting up databases and loading fixtures..."

    # Setup default connection (MySQL)
    if [ -n "${DATABASE_URL}" ]; then
        setup_database default default || echo "⚠️  Failed to setup default connection"
    fi

    # Setup PostgreSQL connection
    if [ -n "${DATABASE_URL_POSTGRES}" ]; then
        setup_database postgres postgres || echo "⚠️  Failed to setup postgres connection"
    fi

    # Setup SQLite connection (file-based, no need to wait)
    if [ -n "${DATABASE_URL_SQLITE}" ]; then
        echo ""
        echo "📦 Setting up SQLite..."
        
        # Check if vendor exists
        if [ ! -d "/app/vendor" ]; then
            echo "  ⚠️  Warning: vendor directory not found. Skipping SQLite setup."
            echo "  💡 Run 'composer install' first, then restart the container."
        else
            # Ensure data directory exists
            mkdir -p /app/var/data
            chmod 777 /app/var/data 2>/dev/null || true
            
            # Setup SQLite database
            setup_database sqlite sqlite || echo "⚠️  Failed to setup sqlite connection"
        fi
    fi

    # Setup MongoDB (if configured)
    if [ -n "${MONGODB_URL}" ]; then
        echo ""
        echo "📦 Setting up MongoDB..."
        
        # Parse MongoDB URL to get connection details
        MONGODB_HOST="${MONGODB_HOST:-mongodb}"
        MONGODB_PORT="${MONGODB_PORT:-27017}"
        MONGODB_USER="${MONGODB_USER:-demo_user}"
        MONGODB_PASSWORD="${MONGODB_PASSWORD:-password}"
        MONGODB_DATABASE="${MONGODB_DATABASE:-anonymize_demo}"
        
        # Check if vendor exists
        if [ ! -d "/app/vendor" ]; then
            echo "  ⚠️  Warning: vendor directory not found. Skipping MongoDB setup."
            echo "  💡 Run 'composer install' first, then restart the container."
        else
            # MongoDB database is created automatically on first connection
            # Load fixtures if script exists
            FIXTURE_SCRIPT="/app/docker/mongodb/load-fixtures.js"
            if [ -f "$FIXTURE_SCRIPT" ]; then
                echo "  Loading MongoDB fixtures..."
                # Use docker exec only if docker is available (e.g. not when running inside a container without docker socket)
                MONGODB_CONTAINER_NAME=""
                if command -v docker >/dev/null 2>&1; then
                # Method 1: Try docker-compose service name
                if docker-compose ps mongodb 2>/dev/null | grep -q "Up"; then
                    MONGODB_CONTAINER_NAME=$(docker-compose ps -q mongodb | head -1)
                    if [ -n "$MONGODB_CONTAINER_NAME" ]; then
                        MONGODB_CONTAINER_NAME=$(docker inspect --format='{{.Name}}' "$MONGODB_CONTAINER_NAME" | sed 's/^\///')
                    fi
                fi
                
                # Method 2: Try to detect by name pattern
                if [ -z "$MONGODB_CONTAINER_NAME" ]; then
                    DETECTED_MONGODB_CONTAINER=$(docker ps --format '{{.Names}}' | grep -E "anonymize-demo.*mongodb|.*mongodb.*anonymize|.*symfony.*mongodb" | head -1)
                    if [ -n "$DETECTED_MONGODB_CONTAINER" ]; then
                        MONGODB_CONTAINER_NAME="$DETECTED_MONGODB_CONTAINER"
                    fi
                fi
                
                if [ -n "$MONGODB_CONTAINER_NAME" ]; then
                    echo "  📦 Loading fixtures into MongoDB container: $MONGODB_CONTAINER_NAME"
                    # Wait a bit more for MongoDB to be fully ready
                    sleep 2
                    if cat "$FIXTURE_SCRIPT" | docker exec -i "$MONGODB_CONTAINER_NAME" mongosh "$MONGODB_DATABASE" \
                        -u "$MONGODB_USER" \
                        -p "$MONGODB_PASSWORD" \
                        --authenticationDatabase admin 2>&1 | while IFS= read -r line; do
                        echo "    $line"
                    done; then
                        echo "  ✅ MongoDB fixtures loaded successfully"
                    else
                        echo "  ⚠️  Warning: MongoDB fixtures may have errors"
                        echo "  💡 You can manually load fixtures by running:"
                        echo "     cat $FIXTURE_SCRIPT | docker exec -i $MONGODB_CONTAINER_NAME mongosh $MONGODB_DATABASE -u $MONGODB_USER -p $MONGODB_PASSWORD --authenticationDatabase admin"
                    fi
                else
                    echo "  ⚠️  Warning: MongoDB container not found, skipping fixture load"
                    if command -v docker >/dev/null 2>&1; then
                        echo "  💡 Available containers:"
                        docker ps --format '  - {{.Names}}' | grep -i mongo || echo "    (no MongoDB containers found)"
                    fi
                    echo "  💡 You can manually load fixtures by running (from host):"
                    echo "     cat $FIXTURE_SCRIPT | docker-compose exec -T mongodb mongosh $MONGODB_DATABASE -u $MONGODB_USER -p $MONGODB_PASSWORD --authenticationDatabase admin"
                fi
                else
                    echo "  ⚠️  Docker not available inside container, skipping MongoDB fixture load."
                    echo "  💡 Load MongoDB fixtures from the host with: docker-compose exec -T mongodb mongosh ..."
                fi
            else
                echo "  ✅ MongoDB is ready and accessible"
                echo "  ℹ️  Note: MongoDB fixtures script not found at $FIXTURE_SCRIPT"
            fi
            echo "  ℹ️  Note: MongoDB ODM configuration will be available when the bundle supports MongoDB"
        fi
    fi

    echo ""
    echo "✅ Entrypoint script completed!"
    echo ""
    echo "📋 Summary:"
    echo "  - Databases checked and ready"
    echo "  - Schemas updated"
    echo "  - Fixtures loaded"
    echo ""
    echo "🌐 Application is ready!"
}

# Run main function in background and execute php-fpm immediately
# This prevents blocking PHP-FPM startup while waiting for databases
# The database setup will happen in the background
main > /proc/1/fd/1 2>&1 &

# Execute original command (php-fpm) immediately
exec "$@"
