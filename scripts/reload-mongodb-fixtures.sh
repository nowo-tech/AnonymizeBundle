#!/bin/bash

# Script to reload MongoDB fixtures in all demos
# Usage: ./scripts/reload-mongodb-fixtures.sh [demo-symfony6|demo-symfony7|demo-symfony8|all]
#        or from scripts/ directory: ./reload-mongodb-fixtures.sh [demo-symfony6|demo-symfony7|demo-symfony8|all]

set -e

DEMO=${1:-all}
BASE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

reload_fixtures() {
    local demo=$1
    local compose_file="$BASE_DIR/demo/$demo/docker-compose.yml"
    local fixture_script="$BASE_DIR/demo/$demo/docker/mongodb/load-fixtures.js"
    
    echo ""
    echo "=========================================="
    echo "🔄 Reloading MongoDB fixtures: $demo"
    echo "=========================================="
    echo ""
    
    if [ ! -f "$compose_file" ]; then
        echo -e "${RED}❌ docker-compose.yml not found: $compose_file${NC}"
        return 1
    fi
    
    if [ ! -f "$fixture_script" ]; then
        echo -e "${RED}❌ Fixture script not found: $fixture_script${NC}"
        return 1
    fi
    
    # Check if MongoDB container is running
    if ! docker-compose -f "$compose_file" ps mongodb | grep -q "Up"; then
        echo -e "${YELLOW}⚠️  MongoDB container is not running${NC}"
        echo "   Starting containers..."
        docker-compose -f "$compose_file" up -d mongodb
        echo "   Waiting for MongoDB to be ready..."
        sleep 5
    fi
    
    # Get MongoDB container name
    MONGODB_CONTAINER=$(docker-compose -f "$compose_file" ps -q mongodb)
    if [ -z "$MONGODB_CONTAINER" ]; then
        echo -e "${RED}❌ MongoDB container not found${NC}"
        return 1
    fi
    
    MONGODB_CONTAINER_NAME=$(docker inspect --format='{{.Name}}' "$MONGODB_CONTAINER" | sed 's/^\///')
    
    echo -e "${YELLOW}📦 Loading fixtures into: $MONGODB_CONTAINER_NAME${NC}"
    
    # Load fixtures
    if cat "$fixture_script" | docker exec -i "$MONGODB_CONTAINER_NAME" mongosh anonymize_demo \
        -u demo_user \
        -p password \
        --authenticationDatabase admin 2>&1; then
        echo -e "${GREEN}✅ Fixtures loaded successfully${NC}"
        return 0
    else
        echo -e "${RED}❌ Error loading fixtures${NC}"
        return 1
    fi
}

main() {
    echo "🚀 MongoDB Fixtures Reloader"
    echo ""
    
    if [ "$DEMO" = "all" ]; then
        for demo in demo-symfony6 demo-symfony7 demo-symfony8; do
            reload_fixtures "$demo"
        done
    else
        reload_fixtures "$DEMO"
    fi
    
    echo ""
    echo "✨ All fixtures reloaded!"
}

main
