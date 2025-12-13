# Performance Improvements

## Overview
Optimasi performa untuk mendukung **500 siswa concurrent** dengan response time optimal.

---

## 1. Rate Limiting (Updated Dec 2024)

Konfigurasi rate limit per endpoint:

| Limiter | Limit/menit | Penggunaan |
|---------|-------------|------------|
| `exam` | 200 | Save answer, navigasi soal |
| `anticheat` | 100 | Violation reports, config |
| `heartbeat` | 60 | Heartbeat, server time, session extend |
| `api` | 150 | API endpoints |
| `login` | 5/IP | Login attempts (anti brute-force) |

**File**: `app/Providers/AppServiceProvider.php`

---

## 2. Database Indexes (Updated Dec 2024)

Index ditambahkan untuk query yang sering digunakan:

```sql
-- Grades (exam monitoring)
grades.end_time
grades.start_time

-- Exam Sessions (time-based queries)  
exam_sessions(start_time, end_time)

-- Students (filtering)
students.classroom_id
students.room_id
students.is_blocked

-- Answers (essay grading)
answers.needs_manual_review

-- Question Bank (filtering)
question_banks.category_id
question_banks.question_type
question_banks.difficulty
```

**Migration**: `2025_12_13_211500_add_performance_indexes.php`

---

## 3. Cache & Session

### Redis Configuration
- Session driver: `redis`
- Cache driver: `redis`
- Persistent connections enabled

### Cache TTL
| Data | TTL |
|------|-----|
| Dashboard stats | 5 menit |
| Classrooms/Lessons | 1 jam |
| Question categories | 5 menit |
| Grade distribution | 10 menit |

---

## 4. Laravel Octane

Untuk performa maksimal, gunakan Laravel Octane dengan FrankenPHP:

```bash
# Install
composer require laravel/octane
php artisan octane:install

# Start server
php artisan octane:start --server=frankenphp --host=0.0.0.0 --port=8000
```

**Config**: `config/octane.php` (default: frankenphp)

---

## 5. Frontend Optimization

### Code Splitting
Vue pages di-lazy load untuk mengurangi initial bundle:
- `vendor-vue`: Vue & Inertia (~460KB)
- `vendor-editor`: TipTap (~630KB)
- `vendor-charts`: Chart.js (~165KB)

### Build Command
```bash
bun run build  # atau npm run build
```

---

## 6. Kapasitas Server

### Untuk 500 Siswa Concurrent
- **CPU**: 4 cores minimum
- **RAM**: 8GB minimum
- **Database**: MySQL 8.0+ dengan connection pooling
- **Cache**: Redis 7.x dengan 512MB memory

### Estimasi Load
- ~5,000 requests/menit
- ~83 requests/detik peak

---

## 7. Monitoring Commands

```bash
# Health check
php artisan exam:health-check

# Performance report
php artisan exam:performance-report

# Cache warmup
php artisan cache:warmup

# Database optimization
php artisan db:optimize
```

---

## 8. Recommended Production Settings

### PHP (php.ini)
```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0
```

### Redis (redis.conf)
```
maxmemory 512mb
maxmemory-policy allkeys-lru
```

### Nginx
```nginx
listen 443 ssl http2;
gzip on;
gzip_types text/plain application/json application/javascript text/css;
```
