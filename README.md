# 📝 Ujian Online

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

**Frontend:**
- Vue.js 3
- Inertia.js
- Tailwind CSS 4
- TinyMCE (Rich Text Editor)
- SweetAlert2
- Chart.js & Vue-ChartJS
- Vue Datepicker
- Vue Countdown
- face-api.js (Face Detection)

## ✨ Fitur

### 👨‍💼 Panel Admin

#### Dashboard
- Statistik overview (total ujian, siswa, sesi aktif)
- Grafik trend 7 hari terakhir (Line Chart)
- Grafik rasio lulus/tidak lulus (Doughnut Chart)
- Grafik distribusi nilai (Bar Chart)
- Tabel ujian terpopuler

#### Manajemen User
- CRUD user admin
- Role-based access (Admin Only)

#### Manajemen Mata Pelajaran
- CRUD mata pelajaran/lesson

#### Manajemen Kelas
- CRUD kelas/classroom
- Relasi dengan siswa

#### Manajemen Siswa
- CRUD data siswa
- Import siswa via Excel
- Assign siswa ke kelas

#### Manajemen Ujian
- CRUD ujian dengan pengaturan lengkap:
  - Durasi ujian
  - Jumlah soal yang ditampilkan (question pool)
  - Acak soal & jawaban
  - Tampilkan hasil
  - Nilai KKM (passing grade)
  - Pengaturan remedial (max attempts)
  - Waktu per soal (opsional)
- Multiple tipe soal:
  - Pilihan Ganda Single (Multiple Choice)
  - Pilihan Ganda Multiple
  - Essay
  - Short Answer
  - True/False
  - Matching (Menjodohkan)
- Import soal via Excel
- Bobot poin per soal
- Deteksi soal duplikat (85% similarity threshold)
- Preview ujian sebagai siswa

#### Bank Soal
- Kategori soal
- Simpan soal untuk digunakan ulang
- Import soal dari bank ke ujian

#### Sesi Ujian
- Buat sesi ujian dengan waktu mulai & selesai
- Enroll siswa/kelas ke sesi ujian (bulk enrollment)
- Monitoring peserta ujian real-time
- Perpanjangan waktu ujian untuk siswa tertentu

#### Penilaian Essay
- Interface khusus untuk menilai soal essay/short answer
- Auto-recalculation nilai setelah penilaian manual

#### Anti-Cheat System 🛡️
Sistem anti-kecurangan komprehensif yang **otomatis aktif** untuk semua ujian:

| Fitur | Status |
|-------|--------|
| Deteksi Tab Switch/Blur | ✅ Aktif |
| Fullscreen Enforcement | ✅ Aktif |
| Block Copy/Paste/Cut | ✅ Aktif |
| Block Right Click | ✅ Aktif |
| Block Keyboard Shortcuts | ✅ Aktif |
| Deteksi DevTools | ✅ Aktif |
| Block Screenshot (PrintScreen) | ✅ Aktif |
| Deteksi Multiple Monitor | ✅ Aktif |
| Deteksi Virtual Machine | ✅ Aktif |
| Deteksi Remote Desktop | ✅ Aktif |
| Single Device Login | ✅ Aktif |
| Face Detection (No Face/Multiple Faces) | ✅ Aktif |

**Konfigurasi Default:**
- Max Violations: 3 (auto-submit setelah 3 pelanggaran)
- Warning Threshold: 2 (peringatan setelah 2 pelanggaran)
- Face Check Interval: 30 detik

**Keyboard Shortcuts yang Diblokir:**
- Ctrl+C, Ctrl+V, Ctrl+X (copy/paste)
- Ctrl+A (select all)
- Ctrl+S (save)
- Ctrl+P (print)
- Ctrl+Shift+I, F12 (DevTools)
- Ctrl+U (view source)
- Alt+Tab (switch window)
- PrintScreen (screenshot)

#### Log Pelanggaran
- Lihat semua pelanggaran anti-cheat
- Filter berdasarkan tipe pelanggaran
- Detail: waktu, siswa, ujian, tipe, deskripsi, IP address
- Badge warna berbeda per tipe pelanggaran

#### Laporan & Export
- Laporan nilai per ujian
- Filter berdasarkan kelas, ujian, sesi
- Export ke Excel
- Export ke PDF:
  - Nilai individu siswa
  - Hasil ujian keseluruhan
  - Laporan per siswa

#### Activity Logs
- Log semua aktivitas sistem
- Filter & search logs
- Export logs
- Cleanup logs lama

#### Analytics & Statistik
- Overview performa keseluruhan
- Analisis per ujian:
  - Tingkat kesulitan soal
  - Distribusi nilai
  - Top performers
- Performa per kelas
- Performa per siswa

### 👨‍🎓 Panel Siswa

#### Login
- Login dengan NISN & password
- Session management (single device login)
- Rate limiting (5 percobaan/menit)
- Cloudflare Turnstile CAPTCHA

#### Dashboard
- Daftar ujian yang tersedia
- Status ujian (belum/sudah dikerjakan)
- Riwayat nilai

#### Mengerjakan Ujian
- Konfirmasi sebelum mulai
- Timer countdown
- Navigasi soal
- Auto-save jawaban
- Submit ujian
- Remedial/retry (jika diizinkan)
- Anti-cheat protection aktif
- Face detection monitoring

#### Hasil Ujian
- Lihat nilai
- Status lulus/tidak lulus
- Review jawaban (jika diizinkan admin)

#### Profil
- Update profil
- Ganti password

### 📱 Progressive Web App (PWA)

Aplikasi mendukung PWA untuk pengalaman seperti aplikasi native:

| Fitur | Deskripsi |
|-------|-----------|
| Installable | Dapat diinstall di desktop/mobile |
| Offline Support | Halaman offline dengan UI retro pixel art |
| Service Worker | Caching assets untuk performa optimal |
| App Icons | Icon berbagai ukuran (72x72 - 512x512) |
| Standalone Mode | Berjalan tanpa address bar browser |

**Service Worker Features:**
- Network-first strategy dengan fallback ke cache
- Auto-update cache saat versi baru tersedia
- Filter request non-HTTP (chrome-extension, dll)
- Offline page dengan desain retro/pixel art

## 📦 Instalasi

### Prerequisites
- PHP >= 8.2
- Composer
- Node.js >= 18
- MySQL/MariaDB

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

## 🔐 Default Credentials

**Admin:**
- Email: `admin@admin.com`
- Password: `password`

## 📁 Struktur Project

```
ujian-online/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/          # Controller untuk panel admin
│   │   │   └── Student/        # Controller untuk panel siswa
│   │   └── Middleware/
│   │       ├── SecurityHeaders.php    # HTTP Security Headers
│   │       ├── SanitizeInput.php      # XSS Input Sanitization
│   │       ├── StudentSingleSession.php
│   │       ├── ThrottleStudentLogin.php
│   │       └── ValidateTurnstile.php  # Cloudflare Turnstile
│   ├── Models/             # Eloquent models
│   └── Services/
│       └── SanitizationService.php    # HTML Sanitization
├── database/
│   ├── migrations/         # Database migrations
│   └── seeders/            # Database seeders
├── public/
│   ├── sw.js               # Service Worker
│   ├── manifest.json       # PWA Manifest
│   ├── offline.html        # Offline page (retro pixel art)
│   ├── icons/              # PWA icons
│   └── models/             # Face detection models
├── resources/
│   ├── js/
│   │   ├── Components/     # Vue components
│   │   ├── Layouts/        # Layout components
│   │   ├── composables/
│   │   │   ├── useAntiCheat.js      # Anti-cheat system
│   │   │   ├── useFaceDetection.js  # Face detection
│   │   │   └── usePWA.js            # PWA install prompt
│   │   └── Pages/          # Inertia pages
│   │       ├── Admin/      # Admin pages
│   │       └── Student/    # Student pages
│   └── views/
│       └── exports/        # PDF templates
└── routes/
    └── web.php             # Web routes
```

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
- Rich text fields (question, options) menggunakan HTML Purifier
- Plain text fields di-strip dari HTML tags
- Excluded fields: password, tokens

### Authentication & Session
- CSRF Protection
- Password Hashing (Bcrypt/Argon2)
- Session Security
- Single Device Login (siswa)
- Rate Limiting (5 login attempts/minute)
- Token Expiration (24 jam)
- Cloudflare Turnstile CAPTCHA

### Anti-Cheat Protection
- Comprehensive browser-based detection
- Face detection (no face/multiple faces)
- Server-side violation logging
- Auto-submit on max violations

### Other Security
- SQL Injection Prevention (Eloquent ORM)
- Role-based Authorization
- Activity Logging
- IP Logging

## 📄 License

MIT License
