#!/usr/bin/env bash
set -euo pipefail

# start.sh
# -----------------------------------------------------------------------------
# Unified startup script for the Koyeb (or similar) deployment when using the
# PHP built-in server instead of Apache/nginx.
#
# Responsibilities:
# 1. Ensure required writable directories exist and have correct perms.
# 2. Run database migrations (idempotent with --force in production).
# 3. Warm up caches (config/route/view) for faster first request.
# 4. Start the PHP built-in server bound to 0.0.0.0:$PORT
#
# This is lightweight and fine for low/medium traffic staging or internal
# environments. For production scale, move to a real web server front-end
# and/or queue workers.
# -----------------------------------------------------------------------------

# Defensive defaults
: "${PORT:=8000}"  # Koyeb/Heroku style env var for exposed port

# Ensure storage and cache write perms (container user may vary)
chmod -R 775 storage bootstrap/cache || true

# If using sqlite, make sure the file exists (already tracked, but guard anyway)
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
  if [ ! -f database/database.sqlite ]; then
    echo "[start.sh] Creating empty SQLite database file"
    touch database/database.sqlite
    chmod 664 database/database.sqlite || true
  fi
fi

# Run migrations (safe if already up-to-date)
if ! php -m | grep -qi pdo_sqlite; then
  echo "[start.sh][WARN] pdo_sqlite extension not loaded. SQLite connection will fail." >&2
fi

php artisan migrate --force || true

# Cache config/routes/views (ignore if fails; app can still boot)
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

echo "[start.sh] Launching PHP built-in server on port ${PORT}"
exec php -S 0.0.0.0:"${PORT}" server.php
