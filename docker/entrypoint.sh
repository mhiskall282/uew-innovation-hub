#!/usr/bin/env bash
# Container start-up for the UEW Innovation Hub.
# Runs on every boot; every step is idempotent.
set -euo pipefail

cd /var/www/html

# 1. Listen on the port the platform gives us (Render sets PORT).
PORT="${PORT:-8080}"
sed -ri "s/^Listen [0-9]+$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# 2. Application key. Render's `generateValue` produces 32 random bytes in
#    base64 without the "base64:" prefix Laravel expects, so add it.
if [[ -z "${APP_KEY:-}" ]]; then
    echo "ERROR: APP_KEY is not set. Generate one with: php artisan key:generate --show" >&2
    exit 1
fi
if [[ "${APP_KEY}" != base64:* ]]; then
    export APP_KEY="base64:${APP_KEY}"
fi

# 3. Cache configuration, routes, views and events using the runtime environment.
php artisan optimize

chown -R www-data:www-data storage bootstrap/cache

# 4. Database schema and first administrator.
if [[ "${RUN_MIGRATIONS:-true}" == "true" ]]; then
    php artisan migrate --force
fi

if [[ -n "${ADMIN_EMAIL:-}" ]]; then
    php artisan hub:create-admin
fi

# Default About / FAQ / Privacy / Terms pages (never overwrites edits).
php artisan db:seed --class=PageSeeder --force

if [[ "${SEED_DEMO_DATA:-false}" == "true" ]]; then
    php artisan db:seed --force
fi

# Accounts must never keep a well-known default password (e.g. demo accounts
# seeded before SEED_PASSWORD was set). Moves them to SEED_PASSWORD.
php artisan hub:secure-demo-accounts || echo "WARNING: some accounts still use a default password; set SEED_PASSWORD" >&2

# 5. Copy legacy report/newsletter PDFs onto the documents disk (skips existing files).
php artisan hub:import-documents || echo "WARNING: legacy document import failed" >&2

# 6. Background queue worker for e-mail, restarted if it exits.
#    For larger deployments run a separate worker service instead (RUN_QUEUE_WORKER=false).
if [[ "${RUN_QUEUE_WORKER:-true}" == "true" && "${QUEUE_CONNECTION:-database}" != "sync" ]]; then
    (
        while true; do
            runuser -u www-data -- php artisan queue:work --sleep=3 --tries=3 --max-time=3600 --memory=128 || true
            sleep 2
        done
    ) &
fi

exec "$@"
