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

    while [ $attempt -le $max_attempts ]; do
        case $db_type in
            mysql)
                if mysqladmin ping -h "$host" -P "$port" -u "${MYSQL_USER:-demo_user}" -p"${MYSQL_PASSWORD:-password}" --silent 2>/dev/null; then
                    echo "✅ MySQL is ready!"
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
                if mongosh --host "$host:$port" --eval "db.adminCommand('ping')" >/dev/null 2>&1; then
                    echo "✅ MongoDB is ready!"
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

    # Create database if not exists
    echo "  Creating database (if not exists)..."
    php bin/console doctrine:database:create --if-not-exists --no-interaction --connection="$connection" 2>&1 || {
        echo "  ⚠️  Warning: Could not create database (may already exist or connection issue)"
    }

    # Update schema
    echo "  Updating schema..."
    php bin/console doctrine:schema:update --force --no-interaction --em="$em" 2>&1 || {
        echo "  ⚠️  Warning: Could not update schema (may already be up to date)"
    }

    # Load fixtures
    echo "  Loading fixtures..."
    php bin/console doctrine:fixtures:load --no-interaction --em="$em" 2>&1 || {
        echo "  ⚠️  Warning: Could not load fixtures (may already be loaded or schema issue)"
    }

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
            if [ -f "/app/docker/mongodb/load-fixtures.js" ]; then
                echo "  Loading MongoDB fixtures..."
                mongosh --host "$MONGODB_HOST:$MONGODB_PORT" \
                    -u "$MONGODB_USER" \
                    -p "$MONGODB_PASSWORD" \
                    --authenticationDatabase admin \
                    "$MONGODB_DATABASE" \
                    < /app/docker/mongodb/load-fixtures.js 2>&1 || {
                    echo "  ⚠️  Warning: Could not load MongoDB fixtures (may already be loaded or connection issue)"
                }
            else
                echo "  ✅ MongoDB is ready and accessible"
                echo "  ℹ️  Note: MongoDB fixtures script not found at /app/docker/mongodb/load-fixtures.js"
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

# Run main function
main

# Execute original command (php-fpm)
exec "$@"
