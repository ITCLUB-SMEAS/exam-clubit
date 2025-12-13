# 📝 Ujian Online (CBT - Computer Based Test)

Selamat datang! 👋

Ini adalah aplikasi **Ujian Online** berbasis web yang dirancang khusus untuk sekolah dan institusi pendidikan. Dengan aplikasi ini, kamu bisa menyelenggarakan ujian secara digital dengan mudah, aman, dan efisien.

> 💡 **Apa itu CBT?** CBT (Computer Based Test) adalah sistem ujian yang dikerjakan menggunakan komputer atau perangkat digital, menggantikan ujian kertas tradisional.

---

## 🎯 Kenapa Pakai Aplikasi Ini?

✅ **Mudah Digunakan** - Interface yang simpel dan intuitif  
✅ **Anti Curang** - Dilengkapi sistem keamanan canggih  
✅ **Hemat Waktu** - Koreksi otomatis untuk soal pilihan ganda  
✅ **Fleksibel** - Bisa diakses dari komputer, tablet, atau HP  
✅ **Laporan Lengkap** - Analisis hasil ujian secara detail  

---

## 📋 Daftar Isi

- [Fitur Utama](#-fitur-utama)
- [Yang Dibutuhkan](#-yang-dibutuhkan)
- [Cara Install](#-cara-install)
- [Panduan Penggunaan](#-panduan-penggunaan)
- [Dokumentasi Teknis](#-dokumentasi-teknis)
- [FAQ](#-faq)

---

## ✨ Fitur Utama

### 👨‍💼 Untuk Admin & Guru

#### 📊 Dashboard
Halaman utama yang menampilkan ringkasan semua aktivitas:
- Total ujian, siswa, dan sesi yang sedang berjalan
- Grafik statistik 7 hari terakhir
- Perbandingan siswa lulus vs tidak lulus
- Daftar ujian paling populer

#### 👥 Kelola Pengguna
- Tambah admin dan guru dengan mudah
- Atur hak akses sesuai peran:
  - **Admin** → Akses penuh ke semua fitur
  - **Guru** → Bisa kelola ujian dan nilai saja
- Keamanan ekstra dengan verifikasi 2 langkah (Google Authenticator)

#### 👨‍🎓 Kelola Siswa
- Input data siswa satu per satu atau import dari Excel sekaligus
- Upload foto siswa secara massal
- Kelompokkan siswa berdasarkan kelas dan ruangan
- Reset password siswa (individual atau sekaligus)
- Blokir siswa yang bermasalah

#### 📝 Kelola Ujian
Buat ujian dengan pengaturan lengkap:
- Atur durasi ujian (dalam menit)
- Pilih berapa soal yang ditampilkan dari bank soal
- Acak urutan soal dan jawaban (anti contek!)
- Tentukan nilai KKM (Kriteria Ketuntasan Minimal)
- Izinkan remedial dengan batas percobaan

**6 Tipe Soal yang Didukung:**
| Tipe | Keterangan |
|------|------------|
| Pilihan Ganda | Pilih satu jawaban benar |
| Pilihan Ganda Multiple | Pilih beberapa jawaban benar |
| Essay | Jawaban panjang/uraian |
| Jawaban Singkat | Jawaban pendek (1-2 kata) |
| Benar/Salah | Pilih benar atau salah |
| Menjodohkan | Cocokkan pasangan yang tepat |

**Fitur Tambahan:**
- Import soal dari Excel
- Atur bobot nilai per soal
- Tandai tingkat kesulitan (mudah/sedang/sulit)
- Deteksi soal duplikat otomatis
- Preview ujian sebelum dipublish
- Duplikasi ujian yang sudah ada
- Support rumus matematika (KaTeX)

#### 🗃️ Bank Soal
Simpan soal-soal untuk digunakan berulang kali:
- Kelompokkan soal berdasarkan kategori
- Import soal dari ujian yang sudah ada
- Filter berdasarkan tipe, kesulitan, atau tag
- Generate tag otomatis dengan AI (Google Gemini)
- Lihat statistik penggunaan setiap soal

#### 📅 Sesi Ujian
Atur kapan ujian bisa dikerjakan:
- Tentukan waktu mulai dan selesai
- Daftarkan siswa satu per satu atau per kelas
- Pantau peserta secara real-time
- Perpanjang waktu untuk siswa tertentu
- Pause/resume ujian kapan saja
- Cetak kartu peserta ujian (PDF)

#### 📋 Absensi Digital
- Siswa check-in dengan token 6 digit atau scan QR Code
- QR Code berubah otomatis setiap 30 detik (anti screenshot)
- Pantau kehadiran secara real-time

#### 👁️ Monitoring Real-time
Pantau ujian yang sedang berlangsung:
- Lihat status setiap siswa (belum mulai/mengerjakan/selesai)
- Progress pengerjaan per siswa
- Jumlah pelanggaran yang terdeteksi
- Tandai siswa yang mencurigakan

#### 🛡️ Sistem Anti Curang
Aplikasi ini dilengkapi sistem keamanan berlapis:

**Deteksi di Browser:**
- Pindah tab/window → Terdeteksi!
- Copy/paste → Diblokir!
- Klik kanan → Diblokir!
- Buka Developer Tools → Terdeteksi!
- Wajah tidak terlihat → Terdeteksi! (Face Detection)
- Suara mencurigakan → Terdeteksi! (Audio Detection)
- Buka ChatGPT/Google → Terdeteksi!
- Diam terlalu lama → Terdeteksi!

**Validasi di Server:**
- Jawab terlalu cepat → Dicurigai!
- Ganti device → Terdeteksi!
- Buka di tab lain → Diblokir!

**Konsekuensi:**
- Peringatan setelah 2 pelanggaran
- Auto-submit setelah 3 pelanggaran
- Semua pelanggaran tercatat dengan screenshot
- Notifikasi langsung ke admin via Telegram

> 📱 **Catatan:** Beberapa fitur anti-cheat otomatis dinonaktifkan di HP karena keterbatasan browser mobile.

#### ✍️ Penilaian Essay
- Interface khusus untuk menilai soal essay
- Nilai banyak jawaban sekaligus
- Nilai total otomatis dihitung ulang

#### 📊 Laporan & Analisis
- Statistik per mata pelajaran
- Grafik distribusi nilai
- Analisis kualitas soal (tingkat kesulitan, daya pembeda)
- Performa siswa per kelas
- Export ke Excel atau PDF
- Deteksi plagiarisme jawaban essay

#### 🔔 Notifikasi
- Notifikasi di dalam aplikasi
- Integrasi Telegram Bot:
  - Info ujian dimulai
  - Alert pelanggaran
  - Ringkasan harian & mingguan

#### 🔧 Maintenance
- Backup database
- Bersihkan data lama
- Kelola cache
- Lihat log aktivitas
- Riwayat login

---

### 👨‍🎓 Untuk Siswa

#### 🏠 Dashboard
- Lihat daftar ujian yang tersedia
- Riwayat ujian yang sudah dikerjakan
- Status pendaftaran ujian

#### 📝 Mengerjakan Ujian
- Tampilan bersih dan mudah dipahami
- Navigasi soal dengan grid nomor
- Timer countdown yang jelas
- Jawaban tersimpan otomatis
- Tandai soal untuk direview nanti
- Konfirmasi sebelum submit
- Lihat hasil (jika diizinkan)

#### 📱 Bisa di HP!
- Tampilan responsif untuk tablet & smartphone
- Bisa di-install seperti aplikasi (PWA)
- Navigasi ramah sentuhan

---

## 💻 Yang Dibutuhkan

Sebelum install, pastikan komputer/server kamu sudah punya:

| Software | Versi Minimum | Keterangan |
|----------|---------------|------------|
| PHP | 8.2 | Bahasa pemrograman utama |
| Composer | 2.x | Package manager PHP |
| Node.js | 18 | Untuk build frontend |
| MySQL | 8.0 | Database |
| Redis | 7.x | Untuk session & cache |

> 💡 **Tips:** Kalau pakai [Laravel Herd](https://herd.laravel.com/) atau [Laragon](https://laragon.org/), sebagian besar sudah terinstall otomatis!

---

## 🚀 Cara Install

### Langkah 1: Download Project

```bash
git clone <repository-url>
cd ujian-online
```

### Langkah 2: Install Dependencies

```bash
# Install package PHP
composer install

# Install package JavaScript
npm install
# atau kalau pakai Bun:
bun install
```

### Langkah 3: Konfigurasi Environment

```bash
# Copy file konfigurasi
cp .env.example .env

# Generate key aplikasi
php artisan key:generate
```

Buka file `.env` dan sesuaikan pengaturan database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ujian_online
DB_USERNAME=root
DB_PASSWORD=password_kamu
```

### Langkah 4: Setup Database

```bash
# Buat tabel-tabel database
php artisan migrate

# (Opsional) Isi data contoh
php artisan db:seed
```

### Langkah 5: Build Frontend

```bash
npm run build
# atau
bun run build
```

### Langkah 6: Jalankan!

```bash
php artisan serve
```

Buka browser dan akses: **http://localhost:8000** 🎉

---

## 📖 Panduan Penggunaan

### Login Pertama Kali

Setelah install, gunakan akun default:

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@admin.com | password |

> ⚠️ **Penting:** Segera ganti password setelah login pertama!

### Alur Penggunaan Dasar

```
1. Login sebagai Admin
        ↓
2. Tambah Data Master (Kelas, Ruangan, Mata Pelajaran)
        ↓
3. Import/Tambah Data Siswa
        ↓
4. Buat Ujian & Tambah Soal
        ↓
5. Buat Sesi Ujian & Daftarkan Siswa
        ↓
6. Siswa Mengerjakan Ujian
        ↓
7. Nilai Essay (jika ada)
        ↓
8. Lihat Laporan & Analisis
```

---

## 📚 Dokumentasi Teknis

Untuk yang ingin mendalami lebih lanjut, dokumentasi lengkap tersedia di folder `docs/`:

| Dokumen | Isi |
|---------|-----|
| [API Documentation](docs/API_DOCUMENTATION.md) | Panduan REST API |
| [Docker Guide](docs/DOCKER.md) | Deploy dengan Docker |
| [Performance Guide](docs/PERFORMANCE.md) | Tips optimasi performa |
| [Security Features](docs/SECURITY_FEATURES.md) | Detail fitur keamanan |

### Menjalankan dengan Docker

```bash
# Start semua service
docker-compose up -d

# Setup database
docker-compose exec app php artisan migrate

# Akses di http://localhost:8000
```

### Command yang Berguna

| Command | Fungsi |
|---------|--------|
| `php artisan exam:backup` | Backup database |
| `php artisan exam:cleanup` | Hapus data lama |
| `php artisan exam:health-check` | Cek kesehatan server |

---

## ❓ FAQ

### Berapa siswa yang bisa ujian bersamaan?
Aplikasi ini dioptimasi untuk **500 siswa** secara bersamaan. Untuk jumlah lebih besar, pertimbangkan menggunakan Laravel Octane.

### Bisa diakses dari HP?
Bisa! Tampilan sudah responsif untuk tablet dan smartphone. Bahkan bisa di-install seperti aplikasi native (PWA).

### Bagaimana kalau internet siswa putus?
Jawaban tersimpan otomatis setiap kali siswa menjawab. Jika koneksi terputus, siswa bisa melanjutkan dari soal terakhir.

### Apakah aman dari kecurangan?
Sangat aman! Aplikasi dilengkapi 15+ metode deteksi kecurangan, baik di browser maupun di server.

### Bisa integrasi dengan sistem lain?
Bisa! Tersedia REST API untuk integrasi dengan sistem informasi sekolah atau aplikasi lainnya.

---

## 🤝 Butuh Bantuan?

Jika mengalami kendala atau punya pertanyaan:

1. Cek dokumentasi di folder `docs/`
2. Buka issue di repository ini
3. Hubungi tim support

---

## 📄 Lisensi

Aplikasi ini adalah software proprietary. Hak cipta dilindungi.

---

<div align="center">

**Dibuat dengan ❤️ menggunakan Laravel & Vue.js**

</div>
