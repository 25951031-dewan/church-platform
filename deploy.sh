#!/usr/bin/env bash
# deploy.sh — Church Platform deployment script
# Usage: bash deploy.sh [optimize|migrate|fresh|rollback]
#
# Commands:
#   optimize   Pre-compile config/routes/views/events (default on deploy)
#   migrate    Run pending database migrations
#   fresh      Drop all tables and re-migrate (DESTRUCTIVE — dev only)
#   rollback   Roll back the last migration batch
#   warm       Warm critical caches after optimize

set -euo pipefail

PHP="${PHP_BIN:-php}"
ARTISAN="$PHP artisan"

log() { echo "[$(date '+%H:%M:%S')] $*"; }
err() { echo "[ERROR] $*" >&2; exit 1; }

# ----- Default: full production deploy sequence -----
if [[ "${1:-deploy}" == "deploy" ]]; then
    log "Putting application into maintenance mode..."
    $ARTISAN down --render="errors::503" --retry=60

    log "Installing Composer dependencies (no dev)..."
    composer install --no-dev --optimize-autoloader --no-interaction

    log "Running database migrations..."
    $ARTISAN migrate --force

    log "Optimizing application..."
    bash "$0" optimize

    log "Bringing application back online..."
    $ARTISAN up
    log "Deploy complete."
    exit 0
fi

case "${1:-optimize}" in
    optimize)
        log "Clearing old cache files..."
        $ARTISAN config:clear
        $ARTISAN route:clear
        $ARTISAN view:clear
        $ARTISAN event:clear

        log "Pre-compiling config / routes / views / events..."
        $ARTISAN config:cache
        $ARTISAN route:cache
        $ARTISAN view:cache
        $ARTISAN event:cache

        log "Warming critical application caches..."
        bash "$0" warm

        log "Optimize complete."
        ;;

    migrate)
        log "Running migrations..."
        $ARTISAN migrate --force
        log "Migrations complete."
        ;;

    fresh)
        [[ "${APP_ENV:-}" == "production" ]] && err "Cannot run 'fresh' in production."
        log "Dropping all tables and re-migrating..."
        $ARTISAN migrate:fresh --seed
        log "Fresh migration complete."
        ;;

    rollback)
        log "Rolling back last migration batch..."
        $ARTISAN migrate:rollback
        log "Rollback complete."
        ;;

    warm)
        log "Warming caches via CacheWarmingService..."
        $ARTISAN cache:warm 2>/dev/null || log "(cache:warm artisan command not found — skipping)"
        log "Cache warm complete."
        ;;

    *)
        echo "Usage: bash deploy.sh [deploy|optimize|migrate|fresh|rollback|warm]"
        exit 1
        ;;
esac
