#!/bin/sh
# =============================================================================
# Container entrypoint. Runs the safe, repeatable production startup sequence
# and then hands control to Apache. Every database step is additive/idempotent:
#   - migrate --force : runs only migrations that have not been applied yet
#   - db:seed --force : uses firstOrCreate / "skip if already seeded" logic
# It never drops, truncates or recreates existing data.
# =============================================================================
set -e

PORT="${PORT:-8080}"
export PORT

echo "[start] Binding Apache to port ${PORT}"
sed -ri "s/^Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/:80>/:${PORT}>/" /etc/apache2/sites-available/000-default.conf

cd /var/www/html

# --- Map Railway's MySQL service variables onto Laravel's DB_* vars ----------
# Only fills the gaps; any explicit DB_* set by the user always wins.
if [ -z "${DB_HOST:-}" ] && [ -n "${MYSQLHOST:-}" ]; then
    export DB_CONNECTION="${DB_CONNECTION:-mysql}"
    export DB_HOST="$MYSQLHOST"
    export DB_PORT="${MYSQLPORT:-3306}"
    export DB_DATABASE="${MYSQLDATABASE:-}"
    export DB_USERNAME="${MYSQLUSER:-}"
    export DB_PASSWORD="${MYSQLPASSWORD:-}"
fi

if [ -z "${DB_URL:-}" ] && [ -n "${MYSQL_URL:-}" ]; then
    export DB_URL="$MYSQL_URL"
fi

# Some platforms expose a single DATABASE_URL. Only adopt it when it is MySQL.
if [ -z "${DB_URL:-}" ] && [ -n "${DATABASE_URL:-}" ]; then
    case "$DATABASE_URL" in
        mysql://*)
            export DB_URL="$DATABASE_URL"
            export DB_CONNECTION="${DB_CONNECTION:-mysql}"
            ;;
    esac
fi

# --- Fail fast on missing critical secrets -----------------------------------
if [ -z "${APP_KEY:-}" ]; then
    echo "[start] ERROR: APP_KEY is not set. Generate one with: php artisan key:generate --show" >&2
    exit 1
fi

if [ -z "${JWT_SECRET:-}" ]; then
    echo "[start] WARNING: JWT_SECRET is not set; falling back to APP_KEY." >&2
fi

# --- Build caches (config must exist before the DB steps) --------------------
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# --- Database: additive migration + idempotent seeding -----------------------
php artisan migrate --force
php artisan db:seed --force

# --- Swagger: regenerate the spec so /api/documentation is always current ----
php artisan l5-swagger:generate

php artisan storage:link || true

chown -R www-data:www-data storage bootstrap/cache

echo "[start] Ready. Serving on 0.0.0.0:${PORT}"
exec apache2-foreground
