#!/bin/bash
# ==========================================================================
# Docker Deployment Script for CBT Ujian Online
# ==========================================================================

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
SECRETS_DIR="$SCRIPT_DIR/secrets"

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  CBT Ujian Online - Docker Deployment  ${NC}"
echo -e "${GREEN}========================================${NC}"

cd "$PROJECT_DIR"

# Check if .env exists
if [ ! -f .env ]; then
    echo -e "${YELLOW}Creating .env from .env.docker...${NC}"
    cp .env.docker .env
    echo -e "${RED}Please edit .env file with your configuration!${NC}"
    exit 1
fi

# ==========================================================================
# Setup Docker Secrets
# ==========================================================================
setup_secrets() {
    echo -e "${YELLOW}Setting up Docker secrets...${NC}"
    
    mkdir -p "$SECRETS_DIR"
    chmod 700 "$SECRETS_DIR"
    
    # Generate APP_KEY if not exist
    if [ ! -f "$SECRETS_DIR/app_key.txt" ]; then
        docker run --rm php:8.4-cli php -r "echo 'base64:' . base64_encode(random_bytes(32));" > "$SECRETS_DIR/app_key.txt"
        echo -e "${GREEN}Generated app_key${NC}"
    fi
    
    # Generate secure passwords if not exist
    if [ ! -f "$SECRETS_DIR/db_root_password.txt" ]; then
        openssl rand -base64 32 | tr -d '\n' > "$SECRETS_DIR/db_root_password.txt"
        echo -e "${GREEN}Generated db_root_password${NC}"
    fi
    
    if [ ! -f "$SECRETS_DIR/db_password.txt" ]; then
        openssl rand -base64 32 | tr -d '\n' > "$SECRETS_DIR/db_password.txt"
        echo -e "${GREEN}Generated db_password${NC}"
    fi
    
    if [ ! -f "$SECRETS_DIR/redis_password.txt" ]; then
        openssl rand -base64 32 | tr -d '\n' > "$SECRETS_DIR/redis_password.txt"
        echo -e "${GREEN}Generated redis_password${NC}"
    fi
    
    # Secure permissions
    chmod 600 "$SECRETS_DIR"/*.txt
}

# ==========================================================================
# Main Deployment
# ==========================================================================

# Setup secrets
setup_secrets

# Create SSL directory
mkdir -p docker/nginx/ssl

# Build images
echo -e "${YELLOW}Building Docker images...${NC}"
docker compose build --no-cache

# Start services
echo -e "${YELLOW}Starting services...${NC}"
docker compose up -d

# Wait for MySQL to be ready
echo -e "${YELLOW}Waiting for MySQL to be ready...${NC}"
sleep 45

# Run migrations
echo -e "${YELLOW}Running database migrations...${NC}"
docker compose exec -T app php artisan migrate --force

# Clear and cache config
echo -e "${YELLOW}Optimizing application...${NC}"
docker compose exec -T app php artisan config:cache
docker compose exec -T app php artisan route:cache
docker compose exec -T app php artisan view:cache

# Create storage link
echo -e "${YELLOW}Creating storage link...${NC}"
docker compose exec -T app php artisan storage:link 2>/dev/null || true

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  Deployment Complete!                  ${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "Application URL: ${YELLOW}http://localhost${NC}"
echo ""
echo -e "${RED}IMPORTANT: Secrets stored in docker/secrets/${NC}"
echo -e "${RED}Keep these files secure and backed up!${NC}"
echo ""
echo -e "Useful commands:"
echo -e "  ${YELLOW}docker compose logs -f${NC}        - View logs"
echo -e "  ${YELLOW}docker compose ps${NC}             - Check status"
echo -e "  ${YELLOW}docker compose down${NC}           - Stop services"
echo -e "  ${YELLOW}docker compose exec app sh${NC}    - Shell access"
