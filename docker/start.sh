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

# --- Public storage symlink (safe to re-run) ---------------------------------
# storage:link errors when public/storage already exists, so only create it the
# first time. An existing real directory is never deleted or overwritten.
if [ -L public/storage ]; then
    echo "[start] public/storage symlink already present; skipping storage:link"
elif [ -e public/storage ]; then
    echo "[start] WARNING: public/storage exists but is not a symlink; leaving it untouched" >&2
else
    php artisan storage:link
fi

# --- Enforce exactly one Apache MPM (mpm_prefork) ----------------------------
# mod_php is not thread-safe, so prefork is required. This is belt-and-braces
# in case the build image or platform enabled mpm_event/mpm_worker too; Apache
# would otherwise refuse to start with:
#   AH00534: apache2: Configuration error: More than one MPM loaded.
a2dismod mpm_event 2>/dev/null || true
a2dismod mpm_worker 2>/dev/null || true
a2enmod mpm_prefork

chown -R www-data:www-data storage bootstrap/cache

echo "[start] Ready. Serving on 0.0.0.0:${PORT}"
exec apache2-foreground
