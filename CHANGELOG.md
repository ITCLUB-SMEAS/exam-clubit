# Changelog

Semua perubahan penting pada project ini akan didokumentasikan di file ini.

---

## [Unreleased]

### 🐛 Bug Fixes

#### Timer & Waktu Ujian
- **Race Condition Timer** - Fix kondisi race saat multiple request update durasi dengan DB transaction lock
- **Pause Time Not Calculated** - Tambah field `total_paused_ms` untuk tracking waktu pause yang akurat
- **Frontend Timer Desync** - Server time sekarang authoritative, sync otomatis saat tab aktif kembali

#### Penilaian
- **Essay Recalculation** - Fix logic status yang tidak konsisten di EssayGradingController
- **Answer Encryption** - Hapus enkripsi dari Answer model yang menyebabkan masalah

#### Anti-Cheat
- **Violation Count Mismatch** - Extend column mapping untuk blur types di Grade model

#### Enrollment
- **Bulk Enrollment Race Condition** - Ganti ke `insertOrIgnore()` dengan unique constraint

#### UI/UX
- **Pause/Resume 404 Error** - Fix URL paths yang salah di Vue component

---

### ✨ Improvements

#### Anti-Cheat System
- **Fullscreen Disabled** - Tidak wajib fullscreen (mengurangi false positive)
- **Face Detection Tolerant** - Confidence 0.3, 5 consecutive fails, 2 min cooldown
- **Audio Detection Realtime** - Monitoring level realtime, cooldown hanya untuk trigger violation
- **HD Screenshot** - Upgrade dari 320x240 (50%) ke 1280x720 (85%) untuk identifikasi lebih jelas

#### Performance
- **Rate Limiting 500 Siswa** - Konfigurasi optimal untuk 500 concurrent users
- **Database Indexes** - Tambah index untuk query yang sering digunakan
- **Laravel Octane** - Support FrankenPHP sebagai default server

#### UX
- **Question Categories Integration** - Kategori soal sekarang di dalam Bank Soal (tab-based)
- **Systems Menu** - Gabung Backup, Maintenance, Cleanup jadi satu menu dropdown
- **HTML5 Support** - Support SVG, MathML, Canvas, Audio/Video di soal

#### Documentation
- **README Beginner-Friendly** - Bahasa kasual tapi profesional, fokus pada pengguna non-teknis

---

### 🔧 DevOps

- **CI/CD Workflows** - GitHub Actions untuk CI, Deploy, dan Docker build
- **Deploy Script** - Script deployment otomatis (`scripts/deploy.sh`)

---

### 📁 Files Changed

#### New Files
```
.github/workflows/ci.yml
.github/workflows/deploy.yml
.github/workflows/docker.yml
scripts/deploy.sh
app/Console/Commands/DecryptAnswerText.php
database/migrations/2025_12_13_100000_add_total_paused_ms_to_grades_table.php
database/migrations/2025_12_13_100001_add_unique_constraint_to_exam_groups_table.php
database/migrations/2025_12_13_211500_add_performance_indexes.php
CHANGELOG.md
```

#### Modified Files
```
README.md
app/Http/Controllers/Admin/EssayGradingController.php
app/Http/Controllers/Admin/ExamPauseController.php
app/Http/Controllers/Admin/QuestionBankController.php
app/Http/Controllers/Admin/QuestionCategoryController.php
app/Http/Controllers/Student/ExamController.php
app/Http/Middleware/SanitizeInput.php
app/Models/Answer.php
app/Models/Grade.php
app/Models/QuestionCategory.php
app/Providers/AppServiceProvider.php
app/Services/AntiCheatService.php
app/Services/ExamTimerService.php
app/Services/SanitizationService.php
config/octane.php
public/sw.js
resources/js/Components/Sidebar.vue
resources/js/Pages/Admin/ExamSessions/Show.vue
resources/js/Pages/Admin/QuestionBank/Index.vue
resources/js/Pages/Student/Exams/Show.vue
resources/js/composables/useAntiCheat.js
resources/js/composables/useAudioDetection.js
resources/js/composables/useFaceDetection.js
routes/web.php
docs/PERFORMANCE.md
```

---

## Kapasitas & Requirements

### Concurrent Users
- **Optimal**: 500 siswa
- **Maximum**: 500+ dengan Laravel Octane

### Server Requirements
| Component | Minimum |
|-----------|---------|
| CPU | 4 cores |
| RAM | 8 GB |
| PHP | 8.2+ |
| MySQL | 8.0+ |
| Redis | 7.x |

---

## Migration Notes

Setelah pull, jalankan:
```bash
php artisan migrate
php artisan config:clear
php artisan cache:clear
bun run build
```
