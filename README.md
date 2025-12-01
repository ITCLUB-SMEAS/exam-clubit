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

**Konfigurasi Default:**
- Max Violations: 3 (auto-submit setelah 3 pelanggaran)
- Warning Threshold: 2 (peringatan setelah 2 pelanggaran)

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

#### Hasil Ujian
- Lihat nilai
- Status lulus/tidak lulus
- Review jawaban (jika diizinkan admin)

#### Profil
- Update profil
- Ganti password

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
│   ├── Http/Controllers/
│   │   ├── Admin/          # Controller untuk panel admin
│   │   └── Student/        # Controller untuk panel siswa
│   ├── Models/             # Eloquent models
│   └── Services/           # Business logic services
├── database/
│   ├── migrations/         # Database migrations
│   └── seeders/            # Database seeders
├── resources/
│   ├── js/
│   │   ├── Components/     # Vue components
│   │   ├── Layouts/        # Layout components
│   │   ├── composables/    # Vue composables (useAntiCheat)
│   │   └── Pages/          # Inertia pages
│   │       ├── Admin/      # Admin pages
│   │       └── Student/    # Student pages
│   └── views/
│       └── exports/        # PDF templates
└── routes/
    └── web.php             # Web routes
```

## 🛡️ Security Features

- CSRF Protection
- XSS Prevention (Input Sanitization)
- SQL Injection Prevention (Eloquent ORM)
- Password Hashing (Bcrypt/Argon2)
- Session Security
- API Rate Limiting
- Token Expiration (24 jam)
- Role-based Authorization
- Comprehensive Anti-Cheat System
- Activity Logging
- IP Logging

## 📄 License

MIT License
