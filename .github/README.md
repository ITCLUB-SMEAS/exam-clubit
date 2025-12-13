# CI/CD Setup

## GitHub Actions Workflows

### 1. CI (`ci.yml`)
Runs on every push/PR to `main` and `develop`:
- PHP Linting (Laravel Pint)
- PHPUnit Tests
- Security Audit

### 2. Deploy (`deploy.yml`)
Runs on push to `main`:
- Builds assets
- Deploys to production server via SSH
- Sends Telegram notification

### 3. Docker (`docker.yml`)
Builds and pushes Docker image to GitHub Container Registry.

## Required Secrets

Add these in GitHub Repository Settings → Secrets:

| Secret | Description |
|--------|-------------|
| `SERVER_HOST` | Production server IP/hostname |
| `SERVER_USER` | SSH username |
| `SERVER_SSH_KEY` | SSH private key |
| `SERVER_PORT` | SSH port (default: 22) |
| `SERVER_PATH` | Project path on server |
| `TELEGRAM_BOT_TOKEN` | Telegram bot token |
| `TELEGRAM_CHAT_ID` | Telegram chat ID for notifications |

## Manual Deploy

```bash
./scripts/deploy.sh
```

## Local Testing

```bash
# Run tests
php artisan test

# Run linting
./vendor/bin/pint

# Security check
composer audit
```
