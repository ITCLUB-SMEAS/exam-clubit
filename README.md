# 📝 Ujian Online (CBT - Computer Based Test)

Aplikasi Ujian Online berbasis web untuk sekolah/institusi pendidikan. Dibangun dengan Laravel 12 dan Vue.js 3.

## 📚 Documentation

Dokumentasi lengkap tersedia di folder [`docs/`](docs/):

- [API Documentation](docs/API_DOCUMENTATION.md) - REST API endpoints & usage
- [Docker Deployment](docs/DOCKER.md) - Docker setup & configuration
- [Performance Guide](docs/PERFORMANCE.md) - Performance optimizations
- [Production Setup](docs/PRODUCTION_OPTIMIZATIONS.md) - Production configuration
- [Security Features](docs/SECURITY_FEATURES.md) - Security implementations
- [Security Audit](docs/SECURITY_AUDIT.md) - Security checklist
- [Security Quick Reference](docs/SECURITY_QUICK_REFERENCE.md) - Quick security guide
- [Immediate Security](docs/IMMEDIATE_SECURITY_IMPLEMENTATION.md) - Critical security fixes

---

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
| Laravel Fortify | 1.25.4 | Authentication |
| Laravel Sanctum | 4.2 | API Token |
| Laravel Octane | 2.13 | High Performance Server |
| Maatwebsite Excel | 3.1 | Import/Export Excel |
| Barryvdh DomPDF | 3.1 | Export PDF |
| Redis | 7.4+ | Session & Cache |
| PragmaRX Google2FA | 8.0 | Two-Factor Auth |

### Frontend
| Technology | Version | Purpose |
|------------|---------|---------|
| Vue.js | 3.5 | Frontend Framework (Composition API) |
| Inertia.js | 2.2 | SPA Bridge |
| Tailwind CSS | 4.1 | Styling |
| Vite | 7.2 | Build Tool |
| TipTap Editor | 3.13 | Rich Text Editor + Math (KaTeX) |
| SweetAlert2 | 5.0 | Alert/Modal |
| Chart.js | 4.5 | Charts & Graphs |
| Vue Datepicker | 12.1 | Date Picker |
| Vue Countdown | 2.1 | Timer Countdown |
| MediaPipe Face Detection | 0.4 | Face Detection |
| html5-qrcode | 2.3 | QR Code Scanner |
| KaTeX | 0.16 | Math Rendering |

### Integrasi
| Service | Purpose |
|---------|---------|
| Cloudflare Turnstile | CAPTCHA Protection |
| Telegram Bot | Notifikasi & Remote Control |
| Google Gemini AI | Question Generator & Auto-Tagging |

---

## 📊 Statistik Project

| Metric | Jumlah |
|--------|--------|
| Total Lines of Code | ~33,000 |
| PHP Files | 152 |
| Vue Components | 88 |
| Database Models | 17 |
| Database Migrations | 65 |
| Controllers | 50 |
| Services | 19 |
| Middleware | 19 |
| Artisan Commands | 19 |
| Vue Composables | 11 |

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
- Upload foto profil

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
- Soft delete dengan restore

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
  - Scoring options (penalty for wrong answer)
- **6 Tipe Soal:**
  - ✅ Pilihan Ganda Single (Multiple Choice)
  - ✅ Pilihan Ganda Multiple (Checkbox)
  - ✅ Essay (Jawaban panjang)
  - ✅ Short Answer (Jawaban singkat)
  - ✅ True/False (Benar/Salah)
  - ✅ Matching (Menjodohkan)
- Import soal via Excel
- Bobot poin per soal (customizable)
- Difficulty level per soal (easy/medium/hard)
- Deteksi soal duplikat (85% similarity threshold)
- Preview ujian sebagai siswa
- Duplikasi ujian (clone)
- Bulk update poin soal
- Bulk delete soal
- Question versioning (riwayat perubahan soal)
- Math equation support (KaTeX)

#### 🗃️ Bank Soal
- Kategori soal (CRUD)
- Simpan soal untuk digunakan ulang
- Import soal dari bank ke ujian
- Import soal dari ujian ke bank
- Filter berdasarkan kategori, tipe soal, difficulty, tags
- **🤖 AI Auto-Generate Tags** - Generate tags otomatis menggunakan Google Gemini AI
- Bulk operations (delete, update tags)
- Export/Import via Excel
- Statistik penggunaan soal (usage count, success rate)

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

#### 🛡️ Anti-Cheat System (Comprehensive)

**Client-Side Detection (Browser):**
| Feature | Description |
|---------|-------------|
| Tab Switch Detection | Deteksi perpindahan tab/window |
| Fullscreen Enforcement | Wajib fullscreen saat ujian (desktop) |
| Copy/Paste Block | Blokir copy, paste, cut |
| Right-Click Block | Blokir klik kanan |
| DevTools Detection | Deteksi buka developer tools |
| Keyboard Shortcut Block | Blokir Ctrl+C, Ctrl+V, F12, dll |
| Window Blur Detection | Deteksi window kehilangan fokus |
| Multiple Monitor Detection | Deteksi penggunaan multi-monitor |
| Virtual Machine Detection | Deteksi VM (VirtualBox, VMware, dll) |
| Face Detection | Deteksi wajah tidak ada / multiple faces (MediaPipe) |
| Audio Detection | Deteksi suara mencurigakan (voice activity) |
| Browser Fingerprint | Deteksi pergantian device mid-exam |
| Network Monitor | Deteksi akses ke ChatGPT, Google, Brainly, dll |
| Idle Detection | Deteksi siswa AFK > 2 menit |
| Single Tab Enforcement | Hanya 1 tab ujian yang boleh aktif |
| Time Anomaly Detection | Deteksi manipulasi waktu sistem |

**Server-Side Validation:**
| Feature | Description |
|---------|-------------|
| Request Timing Analysis | Deteksi jawaban terlalu cepat |
| IP Address Tracking | Log IP setiap request |
| User Agent Tracking | Log browser/device info |
| Session Validation | Validasi session integrity |
| Duplicate Tab Prevention | Cegah buka ujian di tab lain |
| Snapshot Capture | Ambil screenshot saat violation |

**Violation Management:**
- Auto-flag siswa mencurigakan
- Configurable max violations (default: 3)
- Warning threshold sebelum auto-submit
- Auto-submit saat max violations tercapai
- Violation log dengan timestamp & screenshot
- Notifikasi real-time ke admin via Telegram

#### 📊 Analytics & Reports
- Statistik ujian per mata pelajaran
- Grafik distribusi nilai
- Analisis butir soal (Item Analysis):
  - Tingkat kesulitan (difficulty index)
  - Daya pembeda (discrimination index)
  - Efektivitas pengecoh
- Performa siswa per kelas
- Export laporan ke Excel/PDF
- Deteksi plagiarisme jawaban essay

#### 🔔 Notifikasi
- In-app notifications
- Telegram Bot integration:
  - Notifikasi ujian dimulai
  - Notifikasi violation
  - Daily summary
  - Weekly report
  - Remote commands (/status, /stats, dll)

#### 🔧 Maintenance
- Database backup (manual & scheduled)
- Cleanup data lama
- Cache management
- Activity logs viewer
- Login history
- Server health check

---

### 👨‍🎓 Panel Siswa

#### 🏠 Dashboard
- Daftar ujian yang tersedia
- Riwayat ujian yang sudah dikerjakan
- Status enrollment per sesi

#### 📝 Mengerjakan Ujian
- Interface ujian yang clean & responsive
- Navigasi soal (grid nomor soal)
- Timer countdown (total & per soal)
- Auto-save jawaban
- Mark soal untuk review
- Konfirmasi sebelum submit
- Hasil ujian (jika diizinkan admin)

#### 📱 Mobile Friendly
- Responsive design untuk tablet & smartphone
- Landscape warning untuk smartphone (tidak untuk tablet)
- Touch-friendly navigation
- PWA support (installable)

#### 🔐 Keamanan Siswa
- Single session enforcement
- Login throttling (max 5 attempts)
- Session timeout warning
- Secure password hashing

---

## 🔧 Instalasi

### Requirements
- PHP 8.2+
- Composer 2.x
- Node.js 18+ / Bun 1.x
- MySQL 8.0+ / MariaDB 10.6+
- Redis 7.x (untuk session & cache)

### Steps

```bash
# Clone repository
git clone <repository-url>
cd exam

# Install PHP dependencies
composer install

# Install Node dependencies
bun install  # atau npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure database di .env
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=exam_cbt
# DB_USERNAME=root
# DB_PASSWORD=

# Run migrations
php artisan migrate

# Seed initial data (optional)
php artisan db:seed

# Build frontend assets
bun run build  # atau npm run build

# Start development server
php artisan serve
```

### Production dengan Octane

```bash
# Install Swoole extension
pecl install swoole

# Start Octane server
php artisan octane:start --server=swoole --host=0.0.0.0 --port=8000
```

---

## 🐳 Docker Deployment

```bash
# Build dan start containers
docker-compose up -d

# Run migrations
docker-compose exec app php artisan migrate

# Access aplikasi di http://localhost:8000
```

Lihat [Docker Documentation](docs/DOCKER.md) untuk konfigurasi lengkap.

---

## ⚡ Artisan Commands

| Command | Description |
|---------|-------------|
| `php artisan exam:backup` | Backup database |
| `php artisan exam:cleanup` | Cleanup data lama |
| `php artisan exam:cache-warmup` | Warmup cache |
| `php artisan exam:health-check` | Server health check |
| `php artisan exam:performance-report` | Generate performance report |
| `php artisan telegram:daily-summary` | Kirim daily summary ke Telegram |
| `php artisan telegram:weekly-report` | Kirim weekly report ke Telegram |
| `php artisan exam:starting-alert` | Alert ujian akan dimulai |
| `php artisan hash:passwords` | Hash existing plain passwords |
| `php artisan optimize:database` | Optimize database tables |

---

## 🔌 REST API

API tersedia untuk integrasi dengan sistem lain. Autentikasi menggunakan Laravel Sanctum (Bearer Token).

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/login` | Login & get token |
| GET | `/api/exams` | List ujian |
| GET | `/api/exams/{id}` | Detail ujian |
| GET | `/api/students` | List siswa |
| POST | `/api/students` | Create siswa |
| GET | `/api/exam-sessions` | List sesi ujian |
| GET | `/api/grades` | List nilai |

Lihat [API Documentation](docs/API_DOCUMENTATION.md) untuk detail lengkap.

---

## 🔒 Security Features

### Authentication & Authorization
- Laravel Fortify untuk authentication
- Two-Factor Authentication (2FA) dengan Google Authenticator
- Role-based access control (Admin, Guru)
- Session management dengan Redis
- Login throttling & lockout

### Input Validation & Sanitization
- Request validation di semua endpoint
- XSS prevention dengan HTML sanitization
- SQL injection prevention (Eloquent ORM)
- CSRF protection

### Security Headers
- Content-Security-Policy
- X-Frame-Options
- X-Content-Type-Options
- Referrer-Policy
- Permissions-Policy

### Data Protection
- Password hashing (bcrypt)
- Sensitive data encryption
- Soft deletes untuk data recovery
- Activity logging

### Anti-Cheat Security
- Client-side + Server-side validation
- Request timing analysis
- Browser fingerprinting
- Network activity monitoring

Lihat [Security Documentation](docs/SECURITY_FEATURES.md) untuk detail lengkap.

---

## 📁 Struktur Project

```
exam/
├── app/
│   ├── Console/Commands/     # 19 Artisan commands
│   ├── Exports/              # Excel exports
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/        # 33 Admin controllers
│   │   │   ├── Student/      # 7 Student controllers
│   │   │   └── Api/          # API controllers
│   │   ├── Middleware/       # 19 Custom middleware
│   │   └── Requests/         # Form requests
│   ├── Imports/              # Excel imports
│   ├── Jobs/                 # Queue jobs
│   ├── Models/               # 17 Eloquent models
│   ├── Notifications/        # Notification classes
│   ├── Policies/             # Authorization policies
│   └── Services/             # 19 Service classes
├── database/
│   ├── migrations/           # 65 Migration files
│   └── seeders/              # Database seeders
├── resources/
│   └── js/
│       ├── Components/       # Reusable Vue components
│       ├── Layouts/          # Layout components
│       ├── Pages/
│       │   ├── Admin/        # 34 Admin pages
│       │   └── Student/      # 6 Student pages
│       └── composables/      # 11 Vue composables
├── routes/
│   ├── web.php               # Web routes
│   └── api.php               # API routes
└── docs/                     # Documentation
```

---

## 📄 License

This project is proprietary software. All rights reserved.

---

## 👨‍💻 Development

### Running Tests

```bash
php artisan test
```

### Code Style

```bash
# Format PHP code
./vendor/bin/pint

# Build for production
bun run build
```

### Environment Variables

Key environment variables:

```env
# App
APP_ENV=production
APP_DEBUG=false

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=exam_cbt

# Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Telegram Bot
TELEGRAM_BOT_TOKEN=your_bot_token
TELEGRAM_CHAT_ID=your_chat_id

# Cloudflare Turnstile
TURNSTILE_SITE_KEY=your_site_key
TURNSTILE_SECRET_KEY=your_secret_key

# Google Gemini AI
GEMINI_API_KEY=your_api_key
```

---

**Built with ❤️ using Laravel 12 & Vue.js 3**
