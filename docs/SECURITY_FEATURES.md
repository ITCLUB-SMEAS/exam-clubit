# Fitur Keamanan - Sistem Ujian Online

Dokumen ini menjelaskan fitur-fitur keamanan yang telah diimplementasikan dalam sistem ujian online.

---

## 📋 Daftar Fitur Keamanan

| No | Fitur | Status | Versi |
|----|-------|--------|-------|
| 1 | Password Hashing | ✅ Implemented | v1.1.0 |
| 2 | Rate Limiting Login | ✅ Implemented | v1.1.0 |
| 3 | Single Session Management | ✅ Implemented | v1.1.0 |
| 4 | Activity Log / Audit Trail | ✅ Implemented | v1.2.0 |
| 5 | Input Sanitization (XSS Prevention) | ✅ Implemented | v1.2.0 |
| 6 | Anti-Cheat System | ✅ Implemented | v1.3.0 |

---

## 1. Rate Limiting Login

### Deskripsi
Sistem membatasi jumlah percobaan login untuk mencegah serangan brute-force.

### Konfigurasi
- **Maksimal percobaan:** 5 kali
- **Durasi lockout:** 5 menit (300 detik)
- **Key:** Kombinasi NISN + IP Address

### Cara Kerja
1. Setiap percobaan login yang gagal akan dicatat
2. Setelah 5 kali gagal, akun akan dikunci selama 5 menit
3. Pengguna akan melihat pesan error dengan countdown timer
4. Setelah berhasil login, counter akan direset

### File Terkait
- `app/Http/Controllers/Student/LoginController.php`
- `resources/js/Pages/Student/Login/Index.vue`

### Contoh Response
```
Terlalu banyak percobaan login. Silakan coba lagi dalam 5 menit.
```

---

## 2. Single Session Management (Cegah Multi-Login)

### Deskripsi
Sistem memastikan satu akun hanya bisa login di satu perangkat/browser pada satu waktu. Jika login dari perangkat baru, session di perangkat lama akan otomatis di-invalidate.

### Cara Kerja
1. Saat login berhasil, `session_id` disimpan di database (tabel `students`)
2. Setiap request, middleware mengecek apakah `session_id` masih cocok
3. Jika tidak cocok (login dari perangkat lain), user akan di-logout paksa
4. Saat logout, `session_id` di database dikosongkan

### Database Schema
```sql
ALTER TABLE students ADD COLUMN session_id VARCHAR(255) NULL;
ALTER TABLE students ADD COLUMN last_login_at TIMESTAMP NULL;
ALTER TABLE students ADD COLUMN last_login_ip VARCHAR(45) NULL;
```

### File Terkait
- `app/Models/Student.php`
- `app/Http/Middleware/AuthStudent.php`
- `app/Http/Controllers/Student/LogoutController.php`

### Pesan Error Multi-Login
```
Sesi Anda telah berakhir karena login dari perangkat lain. Silakan login kembali.
```

---

## 3. Password Hashing

### Deskripsi
Password siswa di-hash menggunakan bcrypt sebelum disimpan di database.

### Implementasi
- Password di-hash otomatis melalui model casting: `'password' => 'hashed'`
- Login menggunakan `Hash::check()` untuk verifikasi

### Migrasi Password Lama
Untuk meng-hash password yang sudah ada (plain-text):

```bash
php artisan students:hash-passwords
```

Options:
- `--force` - Jalankan tanpa konfirmasi
- `--dry-run` - Preview tanpa melakukan perubahan

---

## 4. Activity Log / Audit Trail

### Deskripsi
Sistem mencatat semua aktivitas penting untuk keperluan audit dan monitoring keamanan.

### Aktivitas yang Dicatat
| Aksi | Modul | Deskripsi |
|------|-------|-----------|
| `login` | auth | Login berhasil |
| `login_failed` | auth | Percobaan login gagal |
| `logout` | auth | User logout |
| `create` | * | Data baru dibuat |
| `update` | * | Data diupdate |
| `delete` | * | Data dihapus |
| `exam_start` | exam | Siswa mulai ujian |
| `exam_end` | exam | Siswa selesai ujian |

### Data yang Disimpan
```php
[
    'user_type'     => 'admin' | 'student',
    'user_id'       => int,
    'user_name'     => string,
    'action'        => string,
    'module'        => string,
    'description'   => string,
    'subject_type'  => string (Model class),
    'subject_id'    => int,
    'old_values'    => json (data sebelum perubahan),
    'new_values'    => json (data setelah perubahan),
    'ip_address'    => string,
    'user_agent'    => string,
    'url'           => string,
    'method'        => string (GET, POST, PUT, DELETE),
    'metadata'      => json (data tambahan),
]
```

### Cara Menggunakan ActivityLogService

```php
use App\Services\ActivityLogService;

// Log aktivitas umum
ActivityLogService::log(
    action: 'custom_action',
    module: 'module_name',
    description: 'Deskripsi aktivitas',
    subject: $model,           // optional
    oldValues: ['key' => 'old'],// optional
    newValues: ['key' => 'new'],// optional
    metadata: ['extra' => 'data'] // optional
);

// Log login
ActivityLogService::logLogin('student', $student, 'success');
ActivityLogService::logLogin('student', $student, 'failed');

// Log logout
ActivityLogService::logLogout('student', $student);

// Log CRUD operations
ActivityLogService::logCreate($model, 'module_name');
ActivityLogService::logUpdate($model, 'module_name', $oldValues);
ActivityLogService::logDelete($model, 'module_name');

// Log exam activities
ActivityLogService::logExamStart($student, $exam, $examSession);
ActivityLogService::logExamEnd($student, $exam, $grade);
```

### Mengakses Activity Logs (Admin)
- **URL:** `/admin/activity-logs`
- **Filter:** Action, Module, User Type, Date Range, Search
- **Export:** CSV download available

### File Terkait
- `app/Models/ActivityLog.php`
- `app/Services/ActivityLogService.php`
- `app/Http/Controllers/Admin/ActivityLogController.php`
- `resources/js/Pages/Admin/ActivityLogs/Index.vue`
- `resources/js/Pages/Admin/ActivityLogs/Show.vue`

### Query Scopes
```php
// Filter by user
ActivityLog::byUser('student', $userId)->get();

// Filter by action
ActivityLog::byAction('login')->get();

// Filter by module
ActivityLog::byModule('auth')->get();

// Filter by date range
ActivityLog::dateBetween($startDate, $endDate)->get();

// Today's logs only
ActivityLog::today()->get();
```

---

## 5. Input Sanitization (XSS Prevention)

### Deskripsi
Sistem secara otomatis membersihkan input dari potensi serangan XSS (Cross-Site Scripting).

### Cara Kerja
1. Middleware `SanitizeInput` otomatis memproses semua request POST, PUT, PATCH
2. Input biasa (nama, judul, dll) di-strip semua HTML tags
3. Rich text fields (soal, opsi jawaban) diizinkan HTML tags yang aman

### Tags HTML yang Diizinkan (Rich Text)
```
p, br, strong, b, em, i, u, s, strike,
ul, ol, li, h1-h6, blockquote, pre, code,
table, thead, tbody, tr, th, td,
img, a, sub, sup, hr, span, div
```

### Pola Berbahaya yang Dihapus
- `<script>` tags
- `onclick`, `onerror`, dan event handlers lainnya
- `javascript:` protocol
- `vbscript:` protocol
- `data:` protocol dengan base64
- `expression()` dalam CSS
- `<iframe>`, `<object>`, `<embed>`, `<applet>` tags
- `<svg>` tags
- `<style>` tags
- `<meta>` refresh tags

### Rich Text Fields (Diizinkan HTML aman)
```php
protected array $richTextFields = [
    'question',
    'option_1',
    'option_2',
    'option_3',
    'option_4',
    'option_5',
    'description',
    'content',
];
```

### Fields yang Dikecualikan dari Sanitization
```php
protected array $excludedFields = [
    'password',
    'password_confirmation',
    'current_password',
    'new_password',
    '_token',
    '_method',
];
```

### Menggunakan SanitizationService Manual
```php
use App\Services\SanitizationService;

// Plain text (strip semua HTML)
$clean = SanitizationService::clean($input);

// Rich text (izinkan HTML aman)
$clean = SanitizationService::cleanRichText($input);

// Sanitize NISN (angka saja)
$nisn = SanitizationService::cleanNisn($input);

// Sanitize email
$email = SanitizationService::cleanEmail($input);

// Sanitize integer
$number = SanitizationService::cleanInt($input);

// Sanitize filename
$filename = SanitizationService::cleanFilename($input);

// Sanitize URL
$url = SanitizationService::cleanUrl($input);

// Sanitize array
$cleanArray = SanitizationService::cleanArray($input, $richText = false);
```

### File Terkait
- `app/Services/SanitizationService.php`
- `app/Http/Middleware/SanitizeInput.php`
- `bootstrap/app.php` (middleware registration)

---

## Testing

### Menjalankan Semua Security Tests
```bash
php artisan test
```

### Test Spesifik
```bash
# Login & Session tests
php artisan test --filter=StudentLoginTest

# Activity Log tests
php artisan test --filter=ActivityLogTest

# Sanitization tests
php artisan test --filter=SanitizationServiceTest
```

### Test Coverage
- **StudentLoginTest:** 14 tests
- **ActivityLogTest:** 14 tests
- **SanitizationServiceTest:** 38 tests
- **Total:** 66+ security-related tests

---

## Konfigurasi

### Environment Variables
```env
# Session
SESSION_LIFETIME=120
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true  # Production only

# Rate Limiting (optional override)
# Default: 5 attempts, 300 seconds lockout
```

---

## Best Practices

1. **Gunakan HTTPS** di production untuk mengamankan transmisi data
2. **Set `SESSION_SECURE_COOKIE=true`** di production
3. **Monitor activity logs** secara berkala untuk deteksi aktivitas mencurigakan
4. **Backup database** secara rutin termasuk activity_logs
5. **Cleanup old logs** untuk menjaga performa (tersedia di admin panel)
6. **Update dependencies** secara rutin untuk patch keamanan

---

## Troubleshooting

### Student Tidak Bisa Login Setelah Update
```bash
php artisan students:hash-passwords
```

### Session Error Terus Muncul
```bash
php artisan cache:clear
php artisan config:clear
```

### Rate Limiter Tidak Reset
```bash
php artisan cache:clear
```

### Activity Logs Terlalu Banyak
Gunakan fitur cleanup di admin panel atau jalankan:
```bash
php artisan tinker
>>> App\Models\ActivityLog::where('created_at', '<', now()->subDays(90))->delete();
```

---

---

## 6. Anti-Cheat System

### Deskripsi
Sistem anti-kecurangan komprehensif yang mendeteksi dan mencatat berbagai perilaku mencurigakan selama ujian berlangsung.

### Fitur Anti-Cheat

| Fitur | Deskripsi | Default |
|-------|-----------|---------|
| Fullscreen Enforcement | Memaksa mode layar penuh selama ujian | Aktif |
| Tab/Window Switch Detection | Mendeteksi perpindahan tab atau window | Aktif |
| Copy/Paste Prevention | Mencegah copy, cut, dan paste | Aktif |
| Right-Click Prevention | Mencegah klik kanan pada halaman ujian | Aktif |
| DevTools Detection | Mendeteksi pembukaan Developer Tools | Aktif |
| Keyboard Shortcut Blocking | Memblokir shortcut keyboard berbahaya | Aktif |
| Window Blur Detection | Mendeteksi hilangnya fokus window | Aktif |

### Shortcut Keyboard yang Diblokir
- `F12` - DevTools
- `Ctrl+Shift+I` - DevTools
- `Ctrl+Shift+J` - DevTools Console
- `Ctrl+Shift+C` - DevTools Inspect
- `Ctrl+U` - View Source
- `Ctrl+C/V/X` - Copy/Paste/Cut (jika diaktifkan)
- `Ctrl+P` - Print
- `Ctrl+S` - Save
- `PrintScreen` - Screenshot

### Jenis Pelanggaran

| Kode | Label | Deskripsi |
|------|-------|-----------|
| `tab_switch` | Pindah Tab/Window | Siswa berpindah ke tab atau window lain |
| `fullscreen_exit` | Keluar Fullscreen | Siswa keluar dari mode fullscreen |
| `copy_paste` | Copy/Paste | Siswa mencoba melakukan copy/paste |
| `right_click` | Klik Kanan | Siswa mencoba klik kanan |
| `devtools` | DevTools | Siswa mencoba membuka Developer Tools |
| `blur` | Window Blur | Window ujian kehilangan fokus |
| `screenshot` | Screenshot | Siswa mencoba mengambil screenshot |
| `keyboard_shortcut` | Shortcut Keyboard | Siswa menggunakan shortcut terlarang |

### Pengaturan Otomatis (Auto-Enabled)

⚡ **Anti-cheat otomatis aktif untuk semua ujian** tanpa perlu konfigurasi manual!

Pengaturan default yang diterapkan:

| Setting | Default Value | Keterangan |
|---------|---------------|------------|
| `anticheat_enabled` | `true` | |
| `fullscreen_required` | `false` | Dinonaktifkan untuk mengurangi false positive |
| `block_tab_switch` | `true` | |
| `block_copy_paste` | `true` | |
| `block_right_click` | `true` | |
| `detect_devtools` | `true` | |
| `detect_face` | `true` | Toleransi tinggi (confidence 0.3) |
| `detect_audio` | `true` | Realtime monitoring |
| `max_violations` | `3` | Auto-submit setelah 3 pelanggaran |
| `warning_threshold` | `2` | Warning setelah 2 pelanggaran |
| `auto_submit_on_max_violations` | `true` | |

> **Note:** Admin tidak perlu mengatur anti-cheat secara manual. Semua ujian (baru maupun yang sudah ada) akan otomatis menggunakan pengaturan default di atas.

### Face Detection Settings
| Setting | Value | Keterangan |
|---------|-------|------------|
| Confidence threshold | 0.3 | Lebih toleran |
| Consecutive fails needed | 5 | Harus gagal 5x berturut-turut |
| Cooldown | 2 menit | Jeda antar violation |

### Audio Detection Settings
| Setting | Value | Keterangan |
|---------|-------|------------|
| Monitoring | Realtime | Level audio selalu dipantau |
| Violation cooldown | 30 detik | Jeda antar trigger violation |

### Screenshot Quality
| Setting | Value |
|---------|-------|
| Resolution | 1280 x 720 (HD) |
| Quality | 85% JPEG |
| File size | ~80-150 KB |

### Threshold dan Tindakan
1. **Warning Threshold** (default: 2 pelanggaran)
   - Menampilkan peringatan keras kepada siswa
   - Grade ditandai sebagai "flagged" untuk ditinjau

2. **Max Violations** (default: 3 pelanggaran)
   - Ujian otomatis diakhiri (auto-submit)
   - Grade ditandai dengan alasan pelanggaran
   - Notifikasi dikirim ke admin via Telegram

### Data yang Dicatat
Setiap pelanggaran mencatat:
```php
[
    'student_id'      => int,
    'exam_id'         => int,
    'exam_session_id' => int,
    'grade_id'        => int,
    'violation_type'  => string,
    'description'     => string,
    'metadata'        => json,  // Data tambahan (key pressed, timestamp, dll)
    'ip_address'      => string,
    'user_agent'      => string,
]
```

### Counter pada Grade
```php
$grade->violation_count       // Total pelanggaran
$grade->tab_switch_count      // Jumlah pindah tab
$grade->fullscreen_exit_count // Jumlah keluar fullscreen
$grade->copy_paste_count      // Jumlah copy/paste
$grade->right_click_count     // Jumlah klik kanan
$grade->blur_count            // Jumlah window blur
$grade->is_flagged            // Ditandai sebagai mencurigakan
$grade->flag_reason           // Alasan ditandai
```

### Menggunakan AntiCheatService

```php
use App\Services\AntiCheatService;

// Record violation
AntiCheatService::recordViolation(
    $student,
    $exam,
    $examSessionId,
    $grade,
    'tab_switch',
    'Siswa berpindah tab',
    ['key' => 'value']
);

// Get violation summary
$summary = AntiCheatService::getViolationSummary($grade);

// Check limits
$exceeded = AntiCheatService::hasExceededLimit($grade, $exam);
$remaining = AntiCheatService::getRemainingViolations($grade, $exam);

// Get config for frontend
$config = AntiCheatService::getAntiCheatConfig($exam);

// Clear violations (admin only)
AntiCheatService::clearViolations($grade, 'Admin cleared');
```

### API Endpoints (Student)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| POST | `/student/anticheat/violation` | Record single violation |
| POST | `/student/anticheat/violations` | Record batch violations |
| GET | `/student/anticheat/status` | Get current violation status |
| GET | `/student/anticheat/config/{examId}` | Get anti-cheat config |
| POST | `/student/anticheat/heartbeat` | Check exam status |

### Frontend Integration (Vue 3)

```javascript
import { useAntiCheat } from '@/composables/useAntiCheat';

// Initialize
const antiCheat = useAntiCheat({
    enabled: true,
    fullscreenRequired: true,
    blockTabSwitch: true,
    blockCopyPaste: true,
    blockRightClick: true,
    detectDevtools: true,
    maxViolations: 10,
    warningThreshold: 3,
    examId: exam.id,
    examSessionId: session.id,
    gradeId: grade.id,

    // Callbacks
    onViolation: (data) => { /* handle violation */ },
    onWarningThreshold: (data) => { /* show warning */ },
    onMaxViolations: (data) => { /* show final warning */ },
    onAutoSubmit: () => { /* auto submit exam */ },
});

// Methods
antiCheat.enterFullscreen();
antiCheat.exitFullscreen();
antiCheat.recordViolation('custom_type', 'description');
antiCheat.cleanup();
```

### File Terkait
- `app/Models/ExamViolation.php`
- `app/Services/AntiCheatService.php`
- `app/Http/Controllers/Student/AntiCheatController.php`
- `resources/js/composables/useAntiCheat.js`
- `resources/js/Pages/Student/Exams/Show.vue`
- `database/migrations/*_create_exam_violations_table.php`
- `database/migrations/*_add_anticheat_columns_to_grades_table.php`
- `database/migrations/*_add_anticheat_settings_to_exams_table.php`

### Admin: Melihat Pelanggaran
Pelanggaran dapat dilihat di:
1. **Halaman Hasil Ujian Siswa** - Menampilkan ringkasan pelanggaran
2. **Activity Logs** - Semua pelanggaran tercatat sebagai activity log
3. **Database** - Query langsung ke tabel `exam_violations`

```php
// Get violations for a grade
$violations = ExamViolation::byGrade($gradeId)->get();

// Get violations by type
$tabSwitches = ExamViolation::byType('tab_switch')->count();

// Get exam session stats
$stats = AntiCheatService::getExamSessionStats($examId, $sessionId);
```

---

## Changelog

### v1.3.0 (2025-05-27)
- ✅ Anti-Cheat System
- ✅ Tab/Window switch detection
- ✅ Fullscreen enforcement
- ✅ Copy/Paste prevention
- ✅ Right-click prevention
- ✅ DevTools detection
- ✅ Keyboard shortcut blocking
- ✅ Violation logging & tracking
- ✅ Auto-enabled for all exams (no manual configuration needed)
- ✅ Auto-submit on max violations
- ✅ Vue 3 useAntiCheat composable
- ✅ Violation summary on result page
- ✅ 23+ new tests for anti-cheat

### v1.2.0 (2025-05-27)
- ✅ Activity Log / Audit Trail
- ✅ Input Sanitization (XSS Prevention)
- ✅ Admin panel untuk melihat activity logs
- ✅ Export activity logs ke CSV
- ✅ 52+ new tests

### v1.1.0 (2025-05-27)
- ✅ Rate Limiting Login
- ✅ Single Session Management
- ✅ Password Hashing untuk Student
- ✅ Fitur Logout untuk Student
- ✅ UI improvements pada halaman login

---

## Kontributor

Dibuat oleh Senior Fullstack Developer untuk meningkatkan keamanan sistem ujian online.