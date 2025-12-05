# 📝 Ujian Online (CBT - Computer Based Test)

Aplikasi Ujian Online berbasis web untuk sekolah/institusi pendidikan. Dibangun dengan Laravel 12 dan Vue.js 3.

## 📋 Table of Contents

- [Tech Stack](#-tech-stack)
- [Statistik Project](#-statistik-project)
- [Fitur Lengkap](#-fitur-lengkap)
- [Instalasi](#-instalasi)
- [Docker Deployment](#-docker-deployment)
- [Artisan Commands](#-artisan-commands)
- [REST API](#-rest-api)
- [Security Features](#-security-features)
- [Struktur Project](#-struktur-project)
- [License](#-license)

---

## 🚀 Tech Stack

### Backend
| Technology | Version | Purpose |
|------------|---------|---------|
| PHP | 8.2+ | Server-side language |
| Laravel | 12 | PHP Framework |
| Laravel Fortify | - | Authentication |
| Laravel Sanctum | - | API Token |
| Laravel Octane | - | High Performance Server |
| Maatwebsite Excel | - | Import/Export Excel |
| Barryvdh DomPDF | - | Export PDF |
| Redis | 7.4+ | Session & Cache |
| PragmaRX Google2FA | - | Two-Factor Auth |

### Frontend
| Technology | Version | Purpose |
|------------|---------|---------|
| Vue.js | 3 | Frontend Framework (Composition API) |
| Inertia.js | - | SPA Bridge |
| Tailwind CSS | 4 | Styling |
| TipTap Editor | - | Rich Text Editor |
| SweetAlert2 | - | Alert/Modal |
| Chart.js | - | Charts & Graphs |
| Vue Datepicker | - | Date Picker |
| Vue Countdown | - | Timer Countdown |
| face-api.js | - | Face Detection |
| html5-qrcode | - | QR Code Scanner |

### Integrasi
| Service | Purpose |
|---------|---------|
| Cloudflare Turnstile | CAPTCHA Protection |
| Telegram Bot | Notifikasi & Remote Control |
| Google Gemini AI | Question Generator |

---

## 📊 Statistik Project

| Metric | Jumlah |
|--------|--------|
| Total Lines of Code | ~26,000 |
| PHP Files | 121 |
| Vue Components | 73 |
| Database Models | 17 |
| Database Migrations | 58 |
| Controllers | 46 |
| Services | 14 |
| Middleware | 12 |

---

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
- Two-Factor Authentication (2FA) dengan Google Authenticator
- Recovery codes untuk backup 2FA

#### 📚 Manajemen Mata Pelajaran
- CRUD mata pelajaran/lesson
- Relasi dengan ujian

#### 🏫 Manajemen Kelas
- CRUD kelas/classroom
- Relasi dengan siswa
- Filter siswa berdasarkan kelas

#### 🏢 Manajemen Ruangan
- CRUD ruangan ujian
- Kapasitas ruangan
- Assign siswa ke ruangan

#### 👨‍🎓 Manajemen Siswa
- CRUD data siswa lengkap
- Import siswa via Excel (bulk)
- Bulk upload foto siswa
- Assign siswa ke kelas & ruangan
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
- Question versioning (riwayat perubahan soal)

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
- Cetak kartu peserta ujian (PDF)

#### 📋 Sistem Absensi
- Token absensi 6 digit per sesi
- QR Code dinamis (rotasi setiap 30 detik)
- Check-in via token atau QR code
- Manual check-in oleh admin
- Monitoring kehadiran real-time
- Regenerate token absensi

#### 👁️ Monitoring Real-time
- Dashboard monitoring ujian aktif
- Status peserta (belum mulai, sedang mengerjakan, selesai, pause)
- Progress pengerjaan per siswa
- Violation count real-time
- Flag siswa mencurigakan
- Last activity tracking

#### ⏱️ Perpanjangan Waktu
- Interface khusus untuk extend waktu
- Perpanjangan per siswa
- Alasan perpanjangan (wajib)
- Riwayat perpanjangan

#### ⏸️ Pause/Resume Ujian
- Pause ujian per siswa dengan alasan
- Pause semua siswa dalam sesi
- Resume individual atau bulk
- Tracking waktu pause

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
- Filter berdasarkan tipe, siswa, ujian, tanggal
- Detail: waktu, siswa, ujian, tipe, deskripsi, IP address
- Badge warna berbeda per tipe pelanggaran
- Snapshot wajah saat pelanggaran (jika tersedia)

#### 📈 Laporan & Export
- Laporan nilai per ujian
- Filter berdasarkan kelas, ujian, sesi
- **Export ke Excel:** Nilai per ujian, Rekap nilai siswa, Activity logs
- **Export ke PDF:** Nilai individu siswa, Hasil ujian keseluruhan, Kartu peserta ujian
- Rate limited (10 request/menit)

#### 📜 Activity Logs
- Log semua aktivitas sistem (login/logout, CRUD, export, dll)
- Filter & search logs
- Export logs ke Excel
- Cleanup logs lama (Admin only)

#### 🔐 Login History
- Riwayat login admin & siswa
- Status login (success/failed)
- IP address & user agent
- Statistik login harian

#### 📊 Analytics & Statistik
- Overview performa keseluruhan
- **Analisis per Ujian:** Item Analysis, Daya pembeda soal, Distribusi nilai
- **Performa per Kelas:** Rata-rata nilai, Tingkat kelulusan, Perbandingan antar kelas
- **Performa per Siswa:** Riwayat nilai, Trend performa, Ranking

#### 🤖 AI Question Generator
- Generate soal otomatis menggunakan Google Gemini AI
- Input: topik, jumlah soal, tipe soal, tingkat kesulitan
- Review & edit sebelum disimpan

#### 🔍 Plagiarism Detection
- Deteksi kemiripan jawaban essay antar siswa
- Similarity percentage
- Highlight bagian yang mirip

#### 💾 Backup & Restore
- Backup database manual
- Download backup file
- Auto cleanup backup > 7 hari

#### 🔧 Maintenance Mode
- Toggle maintenance mode
- Custom maintenance message
- Secret bypass URL
- Allowed IPs whitelist

#### 📱 Telegram Integration
- Notifikasi ujian akan dimulai
- Daily summary & Weekly report (scheduled)
- Server health check alerts
- Token absensi (lihat & generate via bot)
- Alert pelanggaran anti-cheat real-time (dengan foto)

**Bot Commands:**
| Command | Deskripsi |
|---------|-----------|
| `/start` | Mulai bot |
| `/help` | Daftar lengkap perintah |
| `/status` | Ujian aktif saat ini |
| `/students_online` | Siswa sedang mengerjakan |
| `/summary` | Rekap hari ini |
| `/violations` | Pelanggaran hari ini |
| `/health` | Server health check |
| `/token [session_id]` | Lihat/generate token absensi |
| `/search [nama]` | Cari siswa |
| `/score [nisn]` | Nilai siswa |
| `/block [nisn]` | Blokir siswa |
| `/unblock [nisn]` | Unblock siswa |
| `/extend [nisn] [menit]` | Tambah waktu ujian |
| `/pause [nisn]` | Pause ujian siswa |
| `/resume [nisn]` | Resume ujian siswa |
| `/kick [nisn]` | Force submit ujian |
| `/export [exam_id]` | Export PDF hasil ujian |
| `/broadcast [pesan]` | Kirim ke semua admin |

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
- Status ujian (belum dikerjakan, sudah dikerjakan, tidak lulus)
- Riwayat nilai
- Countdown ke ujian berikutnya

#### ✏️ Mengerjakan Ujian
- Konfirmasi sebelum mulai (dengan rules)
- Absensi via token/QR code
- Timer countdown (real-time)
- Navigasi soal (numbered buttons)
- Auto-save jawaban
- Auto-submit saat waktu habis / max violations
- Remedial/retry (jika diizinkan)
- Anti-cheat protection aktif

#### 📊 Hasil Ujian
- Lihat nilai langsung setelah submit
- Status lulus/tidak lulus
- Review jawaban (jika diizinkan admin)

---

### 📱 Progressive Web App (PWA)

| Fitur | Deskripsi |
|-------|-----------|
| Installable | Dapat diinstall di desktop/mobile |
| Offline Support | Halaman offline dengan UI retro pixel art |
| Service Worker | Caching assets untuk performa optimal |
| App Icons | Icon berbagai ukuran (72x72 - 512x512) |
| Standalone Mode | Berjalan tanpa address bar browser |

---

## 📦 Instalasi

### Prerequisites
- PHP >= 8.2
- Composer
- Node.js >= 18
- MySQL/MariaDB
- Redis (untuk session & cache)

### Manual Installation

```bash
# 1. Clone repository
git clone <repository-url>
cd ujian-online

# 2. Install dependencies
composer install
npm install

# 3. Setup environment
cp .env.example .env
php artisan key:generate

# 4. Konfigurasi database di .env
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_DATABASE=ujian_online
# DB_USERNAME=root
# DB_PASSWORD=

# 5. Jalankan migration & seeder
php artisan migrate:fresh --seed

# 6. Build assets
npm run build

# 7. Jalankan server
php artisan serve
```

### Scheduled Tasks

Tambahkan ke crontab:
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### Default Credentials

**Admin:**
- Email: `admin@admin.com`
- Password: `password`

---

## 🐳 Docker Deployment

### Stack Versions

| Component | Version |
|-----------|---------|
| PHP | 8.4-fpm-alpine |
| Node.js | 22-alpine |
| Nginx | 1.27-alpine |
| MySQL | 9.1 |
| Redis | 7.4-alpine |
| Composer | 2.8 |

### Docker Security Features

#### Container Security
- ✅ Non-root user execution
- ✅ Read-only filesystem
- ✅ Dropped capabilities (CAP_DROP ALL)
- ✅ No new privileges (security_opt)
- ✅ Resource limits (CPU & Memory)
- ✅ Health checks on all services
- ✅ Isolated network (frontend/backend)
- ✅ Docker Secrets for sensitive data

#### PHP Security
- ✅ Disabled dangerous functions (exec, shell_exec, etc.)
- ✅ Hidden PHP version (expose_php = Off)
- ✅ open_basedir restriction
- ✅ OPcache enabled with JIT

#### Nginx Security
- ✅ Hidden server version
- ✅ Security headers (HSTS, CSP, X-Frame-Options)
- ✅ Rate limiting (login, API, general)
- ✅ Blocked sensitive files (.env, .git)
- ✅ SSL/TLS 1.2+ only

#### MySQL Security
- ✅ Password validation policy
- ✅ Disabled local_infile
- ✅ Secure file privileges

#### Redis Security
- ✅ Password authentication
- ✅ Disabled dangerous commands (FLUSHDB, CONFIG, etc.)
- ✅ Memory limits

### Quick Start

```bash
# 1. Setup environment
cp .env.docker .env

# 2. Create secrets
mkdir -p docker/secrets
echo "base64:YOUR_APP_KEY" > docker/secrets/app_key.txt
echo "your_db_password" > docker/secrets/db_password.txt
echo "your_root_password" > docker/secrets/db_root_password.txt
echo "your_redis_password" > docker/secrets/redis_password.txt

# 3. Deploy
docker compose build --no-cache
docker compose up -d

# 4. Run migrations
docker compose exec app php artisan migrate --force
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
```

### Docker Services

| Service | Container | Purpose |
|---------|-----------|---------|
| app | cbt-app | PHP-FPM Application |
| nginx | cbt-nginx | Web Server |
| mysql | cbt-mysql | Database |
| redis | cbt-redis | Cache & Session |
| queue | cbt-queue | Queue Worker |
| scheduler | cbt-scheduler | Cron Jobs |

### Useful Commands

```bash
# View logs
docker compose logs -f app

# Shell access
docker compose exec app sh

# Artisan commands
docker compose exec app php artisan migrate
docker compose exec app php artisan cache:clear

# Scale queue workers
docker compose up -d --scale queue=3

# Backup database
docker compose exec mysql mysqldump -u root -p cbt_ujian > backup.sql
```

### SSL/TLS Setup (Let's Encrypt)

```bash
# Generate certificate
docker compose run --rm certbot certonly \
    --webroot \
    --webroot-path=/var/www/html/public \
    -d your-domain.com \
    --email admin@your-domain.com \
    --agree-tos

# Switch to SSL config
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

---

## 🔧 Artisan Commands

```bash
# Backup database
php artisan db:backup

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

# Cleanup old data (logs, violations, etc)
php artisan cleanup:old-data --days=90

# Generate PWA icons
php artisan pwa:icons

# Hash existing plain passwords
php artisan passwords:hash
```

---

## 🔌 REST API

API endpoints untuk integrasi dengan sistem lain:

| Endpoint | Method | Deskripsi |
|----------|--------|-----------|
| `/api/students` | GET | List semua siswa |
| `/api/students/{id}` | GET | Detail siswa |
| `/api/students` | POST | Tambah siswa |
| `/api/students/{id}` | PUT | Update siswa |
| `/api/grades` | GET | List nilai |
| `/api/grades/{id}` | GET | Detail nilai |
| `/api/exams` | GET | List ujian |

- Authentication via Laravel Sanctum (Bearer Token)
- Rate limited
- JSON response

---

## 🛡️ Security Features

### HTTP Security Headers
- `X-Frame-Options: SAMEORIGIN` - Mencegah clickjacking
- `X-Content-Type-Options: nosniff` - Mencegah MIME sniffing
- `X-XSS-Protection: 1; mode=block` - XSS protection
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Content-Security-Policy` - CSP untuk production
- `Strict-Transport-Security` - HSTS untuk HTTPS

### Input Sanitization
- Sanitasi otomatis untuk semua POST/PUT/PATCH request
- Rich text fields menggunakan HTML Purifier
- Plain text fields di-strip dari HTML tags

### Authentication & Session
- CSRF Protection
- Password Hashing (Bcrypt/Argon2)
- Session Security (encrypted, secure cookie)
- Single Device Login (siswa)
- Two-Factor Authentication (admin)
- Rate Limiting (Login: 5 attempts/5 minutes)
- Cloudflare Turnstile CAPTCHA

### Anti-Cheat Protection
- Comprehensive browser-based detection
- Face detection dengan face-api.js
- Server-side violation logging
- Auto-submit on max violations
- IP logging per violation
- Snapshot capture on violation

---

## 📁 Struktur Project

```
ujian-online/
├── app/
│   ├── Console/Commands/       # 10 Artisan commands
│   ├── Exports/                # Excel exports
│   ├── Imports/                # Excel imports
│   ├── Jobs/                   # Queue jobs
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/          # 32 controllers
│   │   │   ├── Api/            # 4 API controllers
│   │   │   └── Student/        # 7 controllers
│   │   └── Middleware/         # 12 middleware
│   ├── Models/                 # 17 Eloquent models
│   ├── Notifications/
│   ├── Policies/
│   └── Services/               # 14 service classes
├── database/
│   ├── migrations/             # 58 migrations
│   └── seeders/
├── docker/
│   ├── nginx/                  # Nginx config
│   ├── php/                    # PHP config
│   ├── mysql/                  # MySQL config
│   └── secrets/                # Docker secrets
├── public/
│   ├── sw.js                   # Service Worker
│   ├── manifest.json           # PWA Manifest
│   ├── offline.html            # Offline page
│   ├── icons/                  # PWA icons
│   └── models/                 # Face detection models
├── resources/
│   ├── js/
│   │   ├── Components/         # Reusable Vue components
│   │   ├── Layouts/
│   │   ├── composables/        # Vue composables
│   │   └── Pages/              # 73 Vue pages
│   └── views/exports/          # PDF templates
├── routes/
│   ├── web.php
│   ├── api.php
│   └── console.php
├── docker-compose.yml
├── docker-compose.prod.yml
├── Dockerfile
└── LICENSE
```

---

## 📄 License

### Functional Source License (FSL-1.1-Apache-2.0)

Copyright © 2024 CBT Ujian Online

#### Licensor
CBT Ujian Online

#### Licensed Work
Ujian Online (CBT - Computer Based Test)

#### Use Grant
You may use, copy, modify, and create derivative works of the Licensed Work for any purpose, except for Competing Uses.

#### Competing Uses
A "Competing Use" means using the Licensed Work to create or offer a product or service that competes with the Licensed Work, including:
- Selling, licensing, or distributing exam/CBT software to third parties
- Offering hosted exam/CBT services (SaaS) to third parties
- Reselling or white-labeling this software
- Creating derivative works for commercial distribution

#### Permitted Uses
- ✅ Internal use by educational institutions (schools, universities, training centers)
- ✅ Customization for your own institution's needs
- ✅ Self-hosting for your own organization
- ✅ Non-commercial educational purposes
- ✅ Personal learning and development

#### Change Date (Per-Version)
Each version converts to Apache 2.0 after **2 years** from its release date:

```
Version 1.0 (Jan 2025) ──► Jan 2027: Apache 2.0
Version 2.0 (Jul 2025) ──► Jul 2027: Apache 2.0
Version 3.0 (Jan 2026) ──► Jan 2028: Apache 2.0
```

Newer versions remain under FSL until their own Change Date.

#### Change License
Apache License, Version 2.0

#### Additional Terms
- No warranty is provided
- Licensor may offer commercial licenses for Competing Uses
- Attribution is required in derivative works

#### Commercial License
For commercial licensing inquiries (SaaS, reselling, white-labeling), please contact the licensor.

---

## 👨‍💻 Author

Developed with ❤️ for Indonesian Education

---

## 🤝 Support

Untuk pertanyaan, bug report, atau feature request, silakan buka issue di repository ini.
