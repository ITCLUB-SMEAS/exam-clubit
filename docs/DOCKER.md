# 🐳 Docker Deployment Guide

Panduan deployment CBT Ujian Online menggunakan Docker dengan konfigurasi keamanan tingkat tinggi.

## 📦 Stack Versions

| Component | Version | Notes |
|-----------|---------|-------|
| PHP | 8.4-fpm-alpine | Latest stable |
| Node.js | 22-alpine | LTS |
| Nginx | 1.27-alpine | Latest stable |
| MySQL | 9.1 | Latest stable |
| Redis | 7.4-alpine | Latest stable |
| Composer | 2.8 | Latest |

## 🛡️ Security Features

### Container Security
- ✅ Non-root user execution
- ✅ Read-only filesystem
- ✅ Dropped capabilities (CAP_DROP ALL)
- ✅ No new privileges (security_opt)
- ✅ Resource limits (CPU & Memory)
- ✅ Health checks on all services
- ✅ Isolated network

### PHP Security
- ✅ Disabled dangerous functions (exec, shell_exec, etc.)
- ✅ Disabled allow_url_fopen & allow_url_include
- ✅ Hidden PHP version (expose_php = Off)
- ✅ open_basedir restriction
- ✅ Secure session configuration
- ✅ OPcache enabled with JIT

### Nginx Security
- ✅ Hidden server version
- ✅ Security headers (HSTS, CSP, X-Frame-Options, etc.)
- ✅ Rate limiting (login, API, general)
- ✅ Blocked sensitive files (.env, .git, etc.)
- ✅ Blocked bad bots & scanners
- ✅ SSL/TLS 1.2+ only
- ✅ Strong cipher suites

### MySQL Security
- ✅ Password validation policy
- ✅ Disabled local_infile
- ✅ Disabled symbolic links
- ✅ Skip name resolve
- ✅ Secure file privileges

### Redis Security
- ✅ Password authentication
- ✅ Disabled dangerous commands (FLUSHDB, CONFIG, etc.)
- ✅ Memory limits
- ✅ Protected mode

## 🚀 Quick Start

### 1. Clone & Setup Environment

```bash
cd /path/to/project

# Copy environment file
cp .env.docker .env

# Edit configuration
nano .env
```

### 2. Configure Required Variables

Edit `.env` dan ubah nilai berikut:

```env
# WAJIB DIUBAH!
APP_KEY=base64:GENERATE_NEW_KEY
DB_PASSWORD=your_strong_password
DB_ROOT_PASSWORD=your_root_password
REDIS_PASSWORD=your_redis_password

# Sesuaikan
APP_URL=https://your-domain.com
```

### 3. Deploy

```bash
# Menggunakan script
./docker/deploy.sh

# Atau manual
docker compose build --no-cache
docker compose up -d
docker compose exec app php artisan migrate --force
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

## 📁 File Structure

```
docker/
├── deploy.sh              # Deployment script
├── nginx/
│   ├── nginx.conf         # Main Nginx config
│   ├── default.conf       # HTTP virtual host
│   ├── default-ssl.conf   # HTTPS virtual host
│   └── ssl/               # SSL certificates
├── php/
│   ├── php.ini            # PHP configuration
│   └── php-fpm.conf       # PHP-FPM pool config
└── mysql/
    ├── my.cnf             # MySQL configuration
    └── init/              # Init scripts
```

## 🔐 SSL/TLS Setup

### Option 1: Let's Encrypt (Recommended)

```bash
# 1. Start tanpa SSL dulu
docker compose up -d nginx

# 2. Generate certificate
docker compose run --rm certbot certonly \
    --webroot \
    --webroot-path=/var/www/html/public \
    -d exam.clubit.id \
    --email admin@clubit.id \
    --agree-tos \
    --no-eff-email

# 3. Switch ke SSL config
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

### Option 2: Custom Certificate

```bash
# Copy certificates ke docker/nginx/ssl/
cp fullchain.pem docker/nginx/ssl/
cp privkey.pem docker/nginx/ssl/
cp chain.pem docker/nginx/ssl/

# Update default-ssl.conf dengan path yang benar
```

## 📊 Useful Commands

```bash
# View logs
docker compose logs -f
docker compose logs -f app
docker compose logs -f nginx

# Check status
docker compose ps

# Shell access
docker compose exec app sh
docker compose exec mysql mysql -u root -p

# Restart services
docker compose restart
docker compose restart app

# Stop all
docker compose down

# Stop & remove volumes (CAUTION: deletes data!)
docker compose down -v

# Rebuild single service
docker compose build --no-cache app
docker compose up -d app
```

## 🔧 Laravel Commands

```bash
# Artisan commands
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:clear
docker compose exec app php artisan queue:restart

# Optimize for production
docker compose exec app php artisan optimize

# Create admin user
docker compose exec app php artisan tinker
```

## 📈 Scaling

```bash
# Scale queue workers
docker compose up -d --scale queue=3

# Scale with load balancer (requires additional config)
docker compose up -d --scale app=3
```

## 🔍 Monitoring

### Health Checks

```bash
# Check all services health
docker compose ps

# Manual health check
curl http://localhost/health
curl http://localhost/fpm-ping
```

### Resource Usage

```bash
docker stats
```

## 🐛 Troubleshooting

### Container won't start

```bash
# Check logs
docker compose logs app

# Check config
docker compose config
```

### Permission issues

```bash
# Fix storage permissions
docker compose exec app chown -R www:www storage bootstrap/cache
docker compose exec app chmod -R 775 storage bootstrap/cache
```

### Database connection refused

```bash
# Wait for MySQL to be ready
docker compose logs mysql

# Test connection
docker compose exec app php artisan db:monitor
```

### Redis connection issues

```bash
# Test Redis
docker compose exec redis redis-cli -a YOUR_PASSWORD ping
```

## 🔄 Backup & Restore

### Database Backup

```bash
# Backup
docker compose exec mysql mysqldump -u root -p cbt_ujian > backup.sql

# Restore
docker compose exec -T mysql mysql -u root -p cbt_ujian < backup.sql
```

### Volume Backup

```bash
# Backup volumes
docker run --rm -v cbt_mysql-data:/data -v $(pwd):/backup alpine tar czf /backup/mysql-backup.tar.gz /data
```

## ⚠️ Production Checklist

- [ ] Change all default passwords
- [ ] Generate new APP_KEY
- [ ] Enable SSL/TLS
- [ ] Configure firewall (only expose 80/443)
- [ ] Setup automated backups
- [ ] Configure log rotation
- [ ] Setup monitoring (Prometheus, Grafana)
- [ ] Configure rate limiting sesuai kebutuhan
- [ ] Review CSP headers
- [ ] Test all functionality
