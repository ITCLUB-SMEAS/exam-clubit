# 📝 Ujian Online (CBT - Computer Based Test)

Aplikasi Ujian Online berbasis web untuk sekolah/institusi pendidikan. Dibangun dengan Laravel 12 dan Vue.js 3.

## 🚀 Tech Stack

**Backend:**
- PHP 8.2+
- Laravel 12
- Laravel Fortify (Authentication)
- Laravel Sanctum (API Token)
- Laravel Octane (High Performance)
- Maatwebsite Excel (Import/Export)
- Barryvdh DomPDF (Export PDF)
- Redis (Session & Cache)

**Frontend:**
- Vue.js 3 (Composition API)
- Inertia.js
- Tailwind CSS 4
- TinyMCE (Rich Text Editor)
- SweetAlert2
- Chart.js & Vue-ChartJS
- Vue Datepicker
- Vue Countdown
- face-api.js (Face Detection)

**Integrasi:**
- Cloudflare Turnstile (CAPTCHA)
- Telegram Bot (Notifikasi)
- Google Gemini AI (Question Generator)

## 📊 Statistik Project

| Metric | Jumlah |
|--------|--------|
| Total Lines of Code | ~22,000 |
| PHP Files | 94 |
| Vue Components | 66 |
| Database Models | 14 |
| Database Migrations | 44 |
| Controllers | 30+ |
| Services | 12 |
| Middleware | 10 |

## ✨ Fitur Lengkap

### 👨‍💼 Panel Admin

#### 📊 Dashboard
- Statistik overview (total ujian, siswa, sesi aktif)
- Grafik trend 7 hari terakhir (Line Chart)
- Grafik rasio lulus/tidak lulus (Doughnut Chart)
- Grafik distribusi nilai (Bar Chart)
- Tabel ujian terpopuler
- Data di-cache untuk performa optimal

#### 👥 Manajemen User
- CRUD user admin/guru
- Role-based access control:
  - **Admin**: Akses penuh ke semua fitur
  - **Guru**: Akses terbatas (tidak bisa kelola user & siswa)

#### 📚 Manajemen Mata Pelajaran
- CRUD mata pelajaran/lesson
- Relasi dengan ujian

#### 🏫 Manajemen Kelas
- CRUD kelas/classroom
- Relasi dengan siswa
- Filter siswa berdasarkan kelas

#### 👨‍🎓 Manajemen Siswa
- CRUD data siswa lengkap
- Import siswa via Excel (bulk)
- Assign siswa ke kelas
- Reset password (individual & bulk)
- Blokir/unblokir siswa
- Filter & search

#### 📝 Manajemen Ujian
- CRUD ujian dengan pengaturan lengkap:
  - Durasi ujian (menit)
  - Jumlah soal yang ditampilkan (question pool)
  - Acak urutan soal
  - Acak urutan jawaban
  - Tampilkan hasil ke siswa
  - Nilai KKM (passing grade)
  - Pengaturan remedial (max attempts)
  - Waktu per soal (opsional)
- **6 Tipe Soal:**
  - ✅ Pilihan Ganda Single (Multiple Choice)
  - ✅ Pilihan Ganda Multiple (Checkbox)
  - ✅ Essay (Jawaban panjang)
  - ✅ Short Answer (Jawaban singkat)
  - ✅ True/False (Benar/Salah)
  - ✅ Matching (Menjodohkan)
- Import soal via Excel
- Bobot poin per soal (customizable)
- Deteksi soal duplikat (85% similarity threshold)
- Preview ujian sebagai siswa
- Duplikasi ujian (clone)
- Bulk update poin soal
- Bulk delete soal

#### 🗃️ Bank Soal
- Kategori soal (CRUD)
- Simpan soal untuk digunakan ulang
- Import soal dari bank ke ujian
- Filter berdasarkan kategori & tipe soal

#### 📅 Sesi Ujian
- Buat sesi ujian dengan waktu mulai & selesai
- Enroll siswa ke sesi ujian:
  - Individual enrollment
  - Bulk enrollment per kelas
- Monitoring peserta ujian real-time
- Perpanjangan waktu ujian untuk siswa tertentu
- Pause/Resume ujian:
  - Per siswa
  - Semua siswa dalam sesi

#### ✍️ Penilaian Essay
- Interface khusus untuk menilai soal essay/short answer
- Bulk grading
- Auto-recalculation nilai setelah penilaian manual
- Filter berdasarkan status (belum/sudah dinilai)

#### 🛡️ Anti-Cheat System
Sistem anti-kecurangan komprehensif yang **otomatis aktif** untuk semua ujian:

| Fitur | Status | Deskripsi |
|-------|--------|-----------|
| Deteksi Tab Switch/Blur | ✅ Aktif | Mendeteksi perpindahan tab/window |
| Fullscreen Enforcement | ✅ Aktif | Wajib mode fullscreen saat ujian |
| Block Copy/Paste/Cut | ✅ Aktif | Mencegah copy-paste |
| Block Right Click | ✅ Aktif | Mencegah klik kanan |
| Block Keyboard Shortcuts | ✅ Aktif | Blokir shortcut berbahaya |
| Deteksi DevTools | ✅ Aktif | Mendeteksi buka Developer Tools |
| Block Screenshot | ✅ Aktif | Blokir tombol PrintScreen |
| Deteksi Multiple Monitor | ✅ Aktif | Mendeteksi monitor tambahan |
| Deteksi Virtual Machine | ✅ Aktif | Mendeteksi VM (VirtualBox, VMware, dll) |
| Deteksi Remote Desktop | ✅ Aktif | Mendeteksi remote access |
| Single Device Login | ✅ Aktif | Hanya 1 device per siswa |
| Face Detection | ✅ Aktif | Deteksi wajah tidak ada/lebih dari 1 |
| Duplicate Tab Detection | ✅ Aktif | Mencegah buka ujian di tab lain |

**Konfigurasi Default:**
- Max Violations: 3 (auto-submit setelah 3 pelanggaran)
- Warning Threshold: 2 (peringatan setelah 2 pelanggaran)
- Face Check Interval: 30 detik

**Keyboard Shortcuts yang Diblokir:**
- `Ctrl+C`, `Ctrl+V`, `Ctrl+X` (copy/paste)
- `Ctrl+A` (select all)
- `Ctrl+S` (save)
- `Ctrl+P` (print)
- `Ctrl+Shift+I`, `F12` (DevTools)
- `Ctrl+U` (view source)
- `Alt+Tab` (switch window)
- `PrintScreen` (screenshot)
- `Ctrl+Shift+C` (inspect element)

#### 📋 Log Pelanggaran
- Lihat semua pelanggaran anti-cheat
- Filter berdasarkan:
  - Tipe pelanggaran
  - Siswa
  - Ujian
  - Tanggal
- Detail: waktu, siswa, ujian, tipe, deskripsi, IP address
- Badge warna berbeda per tipe pelanggaran

#### 📈 Laporan & Export
- Laporan nilai per ujian
- Filter berdasarkan kelas, ujian, sesi
- **Export ke Excel:**
  - Nilai per ujian
  - Rekap nilai siswa
- **Export ke PDF:**
  - Nilai individu siswa (dengan detail jawaban)
  - Hasil ujian keseluruhan
  - Laporan per siswa
- Rate limited (10 request/menit) untuk mencegah abuse

#### 📜 Activity Logs
- Log semua aktivitas sistem:
  - Login/logout
  - CRUD operations
  - Export data
  - dll
- Filter & search logs
- Export logs ke Excel
- Cleanup logs lama (Admin only)
- Detail: user, action, IP address, user agent, timestamp

#### 📊 Analytics & Statistik
- Overview performa keseluruhan
- **Analisis per Ujian:**
  - Item Analysis (tingkat kesulitan soal)
  - Daya pembeda soal
  - Distribusi nilai
  - Top performers
  - Statistik per soal
- **Performa per Kelas:**
  - Rata-rata nilai
  - Tingkat kelulusan
  - Perbandingan antar kelas
- **Performa per Siswa:**
  - Riwayat nilai
  - Trend performa
  - Ranking

#### 🤖 AI Question Generator
- Generate soal otomatis menggunakan Google Gemini AI
- Input: topik, jumlah soal, tipe soal, tingkat kesulitan
- Review & edit sebelum disimpan
- Simpan langsung ke ujian

#### 🔍 Plagiarism Detection
- Deteksi kemiripan jawaban essay antar siswa
- Similarity percentage
- Highlight bagian yang mirip

#### 🔔 Notifikasi
- In-app notifications
- Notifikasi real-time
- Mark as read
- Bulk delete

#### 📱 Telegram Integration
- Notifikasi ujian akan dimulai
- Daily summary (scheduled)
- Weekly report (scheduled)
- Server health check alerts

---

### 👨‍🎓 Panel Siswa

#### 🔐 Login
- Login dengan NISN & password
- Session management (single device login)
- Rate limiting (5 percobaan/5 menit)
- Cloudflare Turnstile CAPTCHA
- Auto-logout jika login dari device lain

#### 🏠 Dashboard
- Daftar ujian yang tersedia
- Status ujian:
  - 🟡 Belum dikerjakan
  - 🟢 Sudah dikerjakan (dengan nilai)
  - 🔴 Tidak lulus (bisa remedial jika diizinkan)
- Riwayat nilai
- Countdown ke ujian berikutnya

#### ✏️ Mengerjakan Ujian
- Konfirmasi sebelum mulai (dengan rules)
- Timer countdown (real-time)
- Navigasi soal (numbered buttons)
- Indikator soal sudah/belum dijawab
- Auto-save jawaban (setiap perubahan)
- Submit ujian manual
- Auto-submit saat waktu habis
- Auto-submit saat max violations
- Remedial/retry (jika diizinkan admin)
- **Anti-cheat protection aktif:**
  - Fullscreen mode
  - Face detection monitoring
  - Violation tracking

#### 📊 Hasil Ujian
- Lihat nilai langsung setelah submit
- Status lulus/tidak lulus
- Review jawaban (jika diizinkan admin):
  - Jawaban benar/salah
  - Kunci jawaban
  - Poin per soal

#### 👤 Profil
- Update data profil
- Ganti password
- Lihat info kelas

---

### 📱 Progressive Web App (PWA)

Aplikasi mendukung PWA untuk pengalaman seperti aplikasi native:

| Fitur | Deskripsi |
|-------|-----------|
| Installable | Dapat diinstall di desktop/mobile |
| Offline Support | Halaman offline dengan UI retro pixel art |
| Service Worker | Caching assets untuk performa optimal |
| App Icons | Icon berbagai ukuran (72x72 - 512x512) |
| Standalone Mode | Berjalan tanpa address bar browser |
| Install Prompt | Prompt install otomatis muncul |

**Service Worker Features:**
- Network-first strategy dengan fallback ke cache
- Auto-update cache saat versi baru tersedia
- Filter request non-HTTP (chrome-extension, dll)
- Offline page dengan desain retro/pixel art

---

### 🔌 REST API

API endpoints untuk integrasi dengan sistem lain:

| Endpoint | Method | Deskripsi |
|----------|--------|-----------|
| `/api/students` | GET | List semua siswa |
| `/api/students/{id}` | GET | Detail siswa |
| `/api/students` | POST | Tambah siswa |
| `/api/students/{id}` | PUT | Update siswa |
| `/api/grades` | GET | List nilai |
| `/api/grades/{id}` | GET | Detail nilai |

- Authentication via Laravel Sanctum (Bearer Token)
- Rate limited
- JSON response

---

## 📦 Instalasi

### Prerequisites
- PHP >= 8.2
- Composer
- Node.js >= 18
- MySQL/MariaDB
- Redis (untuk session & cache)

### Steps

1. Clone repository
```bash
git clone <repository-url>
cd ujian-online
```

2. Install dependencies
```bash
composer install
npm install
```

3. Setup environment
```bash
cp .env.example .env
php artisan key:generate
```

4. Konfigurasi database di `.env`
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ujian_online
DB_USERNAME=root
DB_PASSWORD=

# Redis (opsional, recommended)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

SESSION_DRIVER=redis
CACHE_DRIVER=redis

# Cloudflare Turnstile
TURNSTILE_SITE_KEY=your-site-key
TURNSTILE_SECRET_KEY=your-secret-key

# Telegram (opsional)
TELEGRAM_BOT_TOKEN=your-bot-token
TELEGRAM_CHAT_ID=your-chat-id

# Google Gemini AI (opsional)
GEMINI_API_KEY=your-api-key
```

5. Jalankan migration & seeder
```bash
php artisan migrate:fresh --seed
```

6. Build assets
```bash
npm run build
```

7. Jalankan server
```bash
php artisan serve
```

### Scheduled Tasks (Opsional)

Tambahkan ke crontab untuk fitur scheduled:
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🔐 Default Credentials

**Admin:**
- Email: `admin@admin.com`
- Password: `password`

---

## 📁 Struktur Project

```
ujian-online/
├── app/
│   ├── Console/Commands/       # Artisan commands
│   │   ├── BackupReminder.php
│   │   ├── ExamStartingAlert.php
│   │   ├── SendTelegramDailySummary.php
│   │   ├── SendTelegramWeeklyReport.php
│   │   └── ServerHealthCheck.php
│   ├── Exports/                # Excel exports
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/          # 21 controllers
│   │   │   ├── Api/            # API controllers
│   │   │   └── Student/        # 5 controllers
│   │   └── Middleware/
│   │       ├── AdminOnly.php
│   │       ├── AdminOrGuru.php
│   │       ├── AuthStudent.php
│   │       ├── SecurityHeaders.php
│   │       ├── SanitizeInput.php
│   │       └── ValidateTurnstile.php
│   ├── Models/                 # 14 Eloquent models
│   └── Services/               # 12 service classes
│       ├── ActivityLogService.php
│       ├── AntiCheatService.php
│       ├── BehaviorAnalysisService.php
│       ├── DuplicateQuestionService.php
│       ├── ExamCompletionService.php
│       ├── ExamScoringService.php
│       ├── ExamTimerService.php
│       ├── GeminiService.php
│       ├── ItemAnalysisService.php
│       ├── PlagiarismService.php
│       ├── SanitizationService.php
│       └── TelegramService.php
├── database/
│   ├── migrations/             # 44 migrations
│   └── seeders/
├── public/
│   ├── sw.js                   # Service Worker
│   ├── manifest.json           # PWA Manifest
│   ├── offline.html            # Offline page
│   ├── icons/                  # PWA icons
│   └── models/                 # Face detection models
├── resources/
│   ├── js/
│   │   ├── Components/         # Reusable Vue components
│   │   ├── Layouts/            # Layout components
│   │   ├── composables/
│   │   │   ├── useAntiCheat.js      # Anti-cheat (1,161 lines)
│   │   │   ├── useFaceDetection.js  # Face detection
│   │   │   └── usePWA.js            # PWA install prompt
│   │   └── Pages/              # 66 Vue pages
│   │       ├── Admin/
│   │       └── Student/
│   └── views/
│       └── exports/            # PDF templates
└── routes/
    ├── web.php                 # Web routes
    ├── api.php                 # API routes
    └── console.php             # Console routes
```

---

## 🛡️ Security Features

### HTTP Security Headers
Middleware `SecurityHeaders` menambahkan header keamanan:
- `X-Frame-Options: SAMEORIGIN` - Mencegah clickjacking
- `X-Content-Type-Options: nosniff` - Mencegah MIME sniffing
- `X-XSS-Protection: 1; mode=block` - XSS protection (legacy)
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy` - Kontrol akses kamera/mikrofon
- `Content-Security-Policy` - CSP untuk production
- `Strict-Transport-Security` - HSTS untuk HTTPS

### Input Sanitization
Middleware `SanitizeInput` membersihkan input:
- Sanitasi otomatis untuk semua POST/PUT/PATCH request
- Rich text fields menggunakan HTML Purifier
- Plain text fields di-strip dari HTML tags
- Excluded fields: password, tokens

### Authentication & Session
- CSRF Protection (dengan pengecualian untuk webhook)
- Password Hashing (Bcrypt/Argon2)
- Session Security (encrypted, secure cookie)
- Single Device Login (siswa)
- Rate Limiting:
  - Login: 5 attempts/5 minutes
  - PDF Export: 10 requests/minute
- Token Expiration (24 jam)
- Cloudflare Turnstile CAPTCHA

### Anti-Cheat Protection
- Comprehensive browser-based detection
- Face detection dengan face-api.js
- Server-side violation logging
- Auto-submit on max violations
- IP logging per violation

### Other Security
- SQL Injection Prevention (Eloquent ORM)
- Role-based Authorization
- Activity Logging
- IP Logging
- Global 419 (CSRF) error handling

---

## 🔧 Artisan Commands

```bash
# Kirim reminder backup
php artisan backup:reminder

# Kirim alert ujian akan dimulai
php artisan exam:starting-alert

# Kirim daily summary ke Telegram
php artisan telegram:daily-summary

# Kirim weekly report ke Telegram
php artisan telegram:weekly-report

# Server health check
php artisan server:health-check

# Cleanup expired tokens
php artisan tokens:cleanup

# Generate PWA icons
php artisan pwa:icons
```

---

## 📄 License

MIT License

---

## 👨‍💻 Author

Developed with ❤️ for Indonesian Education
