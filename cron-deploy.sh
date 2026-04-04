#!/bin/bash
# ============================================================
# Church Platform - Auto-Deploy Cron Script
# ============================================================
# Checks for remote changes and deploys if updates available
# Add to crontab for scheduled deployments
# ============================================================

set -e

# Configuration
PROJECT_DIR="/home/username/public_html/church"  # CHANGE THIS
BRANCH="v5-foundation"
DEPLOY_SCRIPT="$PROJECT_DIR/deploy.sh"

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Change to project directory
cd "$PROJECT_DIR"

echo "================================================"
echo "$(date) - Checking for updates..."
echo "================================================"

# Fetch latest from remote
git fetch origin "$BRANCH" --quiet

# Get current and remote commit hashes
LOCAL_COMMIT=$(git rev-parse HEAD)
REMOTE_COMMIT=$(git rev-parse origin/"$BRANCH")

# Compare commits
if [ "$LOCAL_COMMIT" = "$REMOTE_COMMIT" ]; then
    echo -e "${GREEN}✓ Already up to date${NC}"
    echo "Local:  $LOCAL_COMMIT"
    echo "Remote: $REMOTE_COMMIT"
    exit 0
fi

# Changes detected
echo -e "${YELLOW}⚡ Changes detected!${NC}"
echo "Local:  $LOCAL_COMMIT"
echo "Remote: $REMOTE_COMMIT"
echo ""
echo "Starting deployment..."
echo "================================================"

# Run deployment script
if [ -f "$DEPLOY_SCRIPT" ]; then
    bash "$DEPLOY_SCRIPT"
    echo "================================================"
    echo -e "${GREEN}✓ Deployment completed successfully!${NC}"
    echo "$(date)"
else
    echo "ERROR: Deploy script not found: $DEPLOY_SCRIPT"
    exit 1
fi
