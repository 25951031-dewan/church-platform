#!/bin/bash
# ============================================================
# Church Platform - Deploy Script
# ============================================================
# Usage:
#   bash deploy.sh          # Full deploy
#   bash deploy.sh pull     # Git pull only
#   bash deploy.sh migrate  # Run migrations only
#   bash deploy.sh build    # Build assets only
#   bash deploy.sh cache    # Clear & optimize cache only
# ============================================================

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Script directory = project root
DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$DIR"

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  Church Platform - Deploy Script${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

step_pull() {
    echo -e "${YELLOW}[1/5] Pulling latest code...${NC}"
    git fetch origin
    BRANCH=$(git rev-parse --abbrev-ref HEAD)
    echo -e "  Branch: ${GREEN}$BRANCH${NC}"
    git pull origin "$BRANCH"
    echo -e "${GREEN}  ✓ Code updated${NC}"
    echo ""
}

step_composer() {
    echo -e "${YELLOW}[2/5] Installing PHP dependencies...${NC}"
    if command -v composer &> /dev/null; then
        composer install --no-dev --optimize-autoloader --no-interaction
        echo -e "${GREEN}  ✓ Composer dependencies installed${NC}"
    else
        echo -e "${RED}  ✗ Composer not found. Install it: https://getcomposer.org${NC}"
    fi
    echo ""
}

step_migrate() {
    echo -e "${YELLOW}[3/5] Running database migrations...${NC}"
    php artisan migrate --force
    echo -e "${GREEN}  ✓ Migrations complete${NC}"
    echo ""
}

step_build() {
    echo -e "${YELLOW}[4/5] Building frontend assets...${NC}"
    if command -v npm &> /dev/null; then
        if [ ! -d "node_modules" ]; then
            echo "  Installing npm packages first..."
            npm install
        fi
        npm run build
        echo -e "${GREEN}  ✓ Assets built${NC}"
    else
        echo -e "${RED}  ✗ npm not found. Install Node.js: https://nodejs.org${NC}"
    fi
    echo ""
}

step_cache() {
    echo -e "${YELLOW}[5/5] Clearing & optimizing cache...${NC}"
    php artisan optimize:clear
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    # Ensure storage link exists
    if [ ! -L "public/storage" ]; then
        php artisan storage:link
        echo "  Storage linked."
    fi

    echo -e "${GREEN}  ✓ Application optimized${NC}"
    echo ""
}

# Handle specific step or full deploy
case "${1:-all}" in
    pull)
        step_pull
        ;;
    migrate)
        step_migrate
        ;;
    build)
        step_build
        ;;
    cache)
        step_cache
        ;;
    all|"")
        step_pull
        step_composer
        step_migrate
        step_build
        step_cache
        echo -e "${GREEN}========================================${NC}"
        echo -e "${GREEN}  Deploy complete! ✓${NC}"
        echo -e "${GREEN}========================================${NC}"
        ;;
    *)
        echo "Usage: bash deploy.sh [pull|migrate|build|cache]"
        echo "  No argument = full deploy"
        exit 1
        ;;
esac
