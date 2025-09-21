#!/usr/bin/env bash
set -euo pipefail

# docker-start.sh
# Runtime entrypoint for Apache+PHP container.
# Responsibilities:
# 1. Run pending migrations (safe / idempotent).
# 2. (Optional) Rebuild caches if env changed.
# 3. Exec Apache foreground process.

echo "[docker-start] Running migrations..."
php artisan migrate --force || echo "[docker-start][WARN] migrate failed (continuing so you can inspect logs)"

echo "[docker-start] (Re)building caches..."
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

echo "[docker-start] Starting Apache"
exec apache2-foreground
