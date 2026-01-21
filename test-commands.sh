#!/bin/bash

# Script to test all bundle commands in the demos
# Usage: ./test-commands.sh [demo-symfony6|demo-symfony7|demo-symfony8|all]

set +e  # Don't exit on error, we want to continue testing

DEMO=${1:-all}
BASE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Commands to test
COMMANDS=(
    "nowo:anonymize:info --connection default"
    "nowo:anonymize:info --connection postgres"
    "nowo:anonymize:info --connection sqlite"
    "nowo:anonymize:run --connection default --dry-run"
    "nowo:anonymize:run --connection postgres --dry-run"
    "nowo:anonymize:run --connection sqlite --dry-run"
    "nowo:anonymize:history --limit 5"
    "nowo:anonymize:export-db --connection default"
    "nowo:anonymize:generate-column-migration --connection default"
    "nowo:anonymize:generate-mongo-field --scan-documents"
)

test_command() {
    local demo=$1
    local command=$2
    local compose_file="$BASE_DIR/demo/$demo/docker-compose.yml"
    
    echo -e "${YELLOW}Testing: $command${NC}"
    
    if docker-compose -f "$compose_file" ps php | grep -q "Up"; then
        if docker-compose -f "$compose_file" exec -T php php bin/console $command > /tmp/command_output.txt 2>&1; then
            echo -e "${GREEN}✅ Success${NC}"
            if [ -s /tmp/command_output.txt ]; then
                echo "   Output (first 5 lines):"
                head -5 /tmp/command_output.txt | sed 's/^/   /'
            fi
            return 0
        else
            echo -e "${RED}❌ Error${NC}"
            echo "   Error (first 10 lines):"
            head -10 /tmp/command_output.txt | sed 's/^/   /'
            return 1
        fi
    else
        echo -e "${YELLOW}⚠️  Container is not running${NC}"
        return 2
    fi
}

test_demo() {
    local demo=$1
    local compose_file="$BASE_DIR/demo/$demo/docker-compose.yml"
    
    echo ""
    echo "=========================================="
    echo "🧪 Testing: $demo"
    echo "=========================================="
    echo ""
    
    if [ ! -f "$compose_file" ]; then
        echo -e "${RED}❌ docker-compose.yml file not found: $compose_file${NC}"
        return 1
    fi
    
    # Check if container is running
    if ! docker-compose -f "$compose_file" ps php | grep -q "Up"; then
        echo -e "${YELLOW}⚠️  Container 'php' is not running for $demo${NC}"
        echo "   To start it: docker-compose -f $compose_file up -d"
        return 2
    fi
    
    local success=0
    local failed=0
    local skipped=0
    
    for cmd in "${COMMANDS[@]}"; do
        test_command "$demo" "$cmd"
        case $? in
            0) ((success++)) ;;
            1) ((failed++)) ;;
            2) ((skipped++)) ;;
        esac
        echo ""
    done
    
    echo "=========================================="
    echo "📊 Summary for $demo:"
    echo "   ✅ Successful: $success"
    echo "   ❌ Failed: $failed"
    echo "   ⚠️  Skipped: $skipped"
    echo "=========================================="
    echo ""
}

main() {
    echo "🚀 Starting AnonymizeBundle command tests"
    echo ""
    
    if [ "$DEMO" = "all" ]; then
        for demo in demo-symfony6 demo-symfony7 demo-symfony8; do
            test_demo "$demo"
        done
    else
        test_demo "$DEMO"
    fi
    
    echo "✨ Tests completed"
}

main
