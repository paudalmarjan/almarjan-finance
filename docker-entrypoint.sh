#!/bin/bash
set -e

echo "==> Running Laravel setup..."

# Generate app key if not set
if [ -z "$APP_KEY" ]; then
    echo "==> Generating APP_KEY..."
    php artisan key:generate --force
fi

# Run database migrations
echo "==> Running migrations..."
php artisan migrate --force

# Run database seeder only if users table is empty (first deploy only)
USER_COUNT=$(php artisan tinker --execute="echo \App\Models\User::count();" 2>/dev/null | tail -1)
if [ "$USER_COUNT" = "0" ]; then
    echo "==> Seeding database..."
    php artisan db:seed --force
fi

# Clear and optimize caches
echo "==> Optimizing..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Setup complete. Starting Apache..."
exec apache2-foreground
