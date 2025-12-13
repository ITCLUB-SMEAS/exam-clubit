#!/bin/bash
set -e

# ============================================
# Deploy Script for Ujian Online CBT
# ============================================

echo "🚀 Starting deployment..."

# Go to project directory
cd "$(dirname "$0")/.."

# Maintenance mode
php artisan down --retry=60

# Pull latest code
git pull origin main

# Install PHP dependencies
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Install JS dependencies & build
bun install
bun run build

# Run migrations
php artisan migrate --force

# Clear and optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Restart services
php artisan octane:reload 2>/dev/null || true
php artisan queue:restart 2>/dev/null || true

# Exit maintenance mode
php artisan up

echo "✅ Deployment completed!"
