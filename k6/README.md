# K6 Load Testing untuk CBT

## Prerequisites
- K6 installed (`k6 version`)
- Server running (`php artisan serve` atau Octane)

## Test Files

| File | Deskripsi | VUs | Duration |
|------|-----------|-----|----------|
| `api-auth.js` | Test login/logout flow | 10 | 2m |
| `api-exams.js` | Test exam endpoints | 20 | 2m |
| `api-students.js` | Test student endpoints | 15 | 2m |
| `api-grades.js` | Test grades endpoints | 15 | 2m |
| `stress-test.js` | Find breaking point | 50-150 | 20m |
| `spike-test.js` | Sudden traffic surge | 5-200 | 6m |

## Usage

```bash
# Basic test
k6 run api-auth.js

# Custom base URL
k6 run -e BASE_URL=https://exam.example.com api-auth.js

# With HTML report
k6 run --out json=results.json api-exams.js

# Run all API tests
k6 run api-auth.js && k6 run api-exams.js && k6 run api-students.js && k6 run api-grades.js

# Stress test (WARNING: heavy load)
k6 run stress-test.js

# Spike test (simulasi ujian dimulai)
k6 run spike-test.js
```

## Configuration

Edit `config.js` untuk mengubah:
- `BASE_URL` - URL server
- `ADMIN_CREDENTIALS` - Login credentials
- `THRESHOLDS` - Performance thresholds

## Thresholds

Default thresholds:
- 95% requests < 500ms
- 99% requests < 1000ms
- Error rate < 1%

## Tips

1. Jalankan di environment terpisah (jangan production)
2. Monitor server resources saat test (CPU, RAM, DB connections)
3. Mulai dari load kecil, naikkan bertahap
4. Cek Laravel logs untuk errors
