# 🔒 Security Features - Quick Reference Guide

## 🚀 Quick Start

### Password Requirements (NEW)
```
✅ Minimal 8 karakter
✅ Minimal 1 huruf BESAR (A-Z)
✅ Minimal 1 huruf kecil (a-z)
✅ Minimal 1 angka (0-9)

❌ password123    → DITOLAK (tidak ada huruf besar)
❌ PASSWORD123    → DITOLAK (tidak ada huruf kecil)
❌ Password       → DITOLAK (tidak ada angka)
✅ Password123    → DITERIMA
```

### File Upload Limits (NEW)
```
📸 Images: Max 2MB (JPG, PNG, GIF, WebP)
📄 Documents: Max 5MB (PDF, XLS, XLSX, CSV, TXT)

❌ Blocked: PHP, EXE, SH, JS, HTML, SVG
❌ Malicious content detection aktif
✅ Auto image optimization
```

### API Validation (NEW)
```php
// NISN: hanya angka
"nisn" => "1234567890"  ✅
"nisn" => "ABC123"      ❌

// Name: hanya huruf dan spasi
"name" => "John Doe"    ✅
"name" => "John123"     ❌
```

### Server-Side Anti-Cheat (NEW)
```
🤖 Automation tool detection
⚡ Rapid submission detection (< 2 detik)
🎯 Uniform timing pattern detection
🌐 IP change detection
📋 Copy-paste detection
```

---

## 💻 Developer Guide

### 1. Encrypt Sensitive Data

```php
// Model
use App\Models\Traits\HasEncryptedAttributes;

class YourModel extends Model
{
    use HasEncryptedAttributes;
    
    protected $encrypted = [
        'sensitive_field_1',
        'sensitive_field_2',
    ];
}

// Automatic encryption/decryption
$model->sensitive_field_1 = 'secret data';  // Auto encrypted
echo $model->sensitive_field_1;              // Auto decrypted
```

### 2. Strong Password Validation

```php
use App\Rules\StrongPassword;

$request->validate([
    'password' => ['required', 'confirmed', new StrongPassword()],
]);

// Custom requirements
new StrongPassword(
    minLength: 10,              // Default: 8
    requireUppercase: true,     // Default: true
    requireLowercase: true,     // Default: true
    requireNumbers: true,       // Default: true
    requireSpecialChars: true   // Default: false
);
```

### 3. API Request Validation

```php
// Create FormRequest
use App\Http\Requests\Api\YourRequest;

class YourRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'field' => ['required', 'string', 'regex:/^[a-zA-Z]+$/'],
        ];
    }
    
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation error',
            'errors' => $validator->errors()
        ], 422));
    }
}

// Use in controller
public function store(YourRequest $request)
{
    $validated = $request->validated();
    // ...
}
```

### 4. File Upload with Optimization

```php
use App\Services\ImageOptimizationService;

// Optimize and store image
$path = ImageOptimizationService::optimizeAndStore(
    file: $request->file('photo'),
    path: 'photos',
    maxWidth: 800,    // Default: 800px
    quality: 85       // Default: 85%
);

// Result: Resized, compressed, and stored
```

### 5. Apply Server-Side Anti-Cheat

```php
// routes/web.php
Route::post('/your-route', [YourController::class, 'method'])
    ->middleware('anticheat.server');

// Automatic detection:
// - Automation tools
// - Rapid submissions
// - Timing patterns
// - IP changes
// - Copy-paste
```

---

## 🔧 Configuration

### Environment Variables
```env
# No additional config needed
# Uses existing APP_KEY for encryption
```

### Middleware Aliases
```php
// bootstrap/app.php
'anticheat.server' => \App\Http\Middleware\ServerSideAntiCheat::class,
'file.validate' => \App\Http\Middleware\ValidateFileUpload::class,
```

---

## 🧪 Testing

### Run Security Tests
```bash
# All security tests
php artisan test --filter=ImmediateSecurityTest

# Specific test
php artisan test --filter=password_must_meet_complexity_requirements
```

### Manual Testing

**Password Validation:**
```bash
# Try creating user with weak password
curl -X POST /admin/users \
  -d "password=weak" \
  -d "password_confirmation=weak"
# Expected: Validation error
```

**File Upload:**
```bash
# Try uploading PHP file
curl -X POST /admin/profile/photo \
  -F "photo=@malicious.php"
# Expected: File tidak valid
```

**API Validation:**
```bash
# Try invalid NISN
curl -X POST /api/students \
  -H "Authorization: Bearer TOKEN" \
  -d "nisn=ABC123"
# Expected: NISN hanya boleh berisi angka
```

---

## 🐛 Troubleshooting

### Issue: "Password tidak memenuhi requirements"
**Solution:** Password harus minimal 8 karakter dengan huruf besar, kecil, dan angka.
```
❌ password123
✅ Password123
```

### Issue: "File tidak valid atau tidak diizinkan"
**Solution:** 
- Check file size (max 2MB untuk images, 5MB untuk documents)
- Check file type (hanya JPG, PNG, GIF, WebP, PDF, XLS, XLSX, CSV, TXT)
- Pastikan file bukan malicious (tidak ada PHP code, script tags)

### Issue: "Validation error" dari API
**Solution:** Check API request format:
```json
{
  "nisn": "1234567890",        // Hanya angka
  "name": "John Doe",          // Hanya huruf dan spasi
  "classroom_id": 1,           // Must exist
  "password": "Password123",   // Strong password
  "gender": "L"                // L atau P
}
```

### Issue: Anti-cheat violation terdeteksi
**Solution:** 
- Jangan gunakan automation tools (Selenium, Puppeteer)
- Jangan submit jawaban terlalu cepat (< 2 detik)
- Jangan ganti IP address saat ujian
- Jangan copy-paste dari web

---

## 📊 Monitoring

### Check Violations
```sql
-- Recent violations
SELECT * FROM exam_violations 
WHERE created_at > NOW() - INTERVAL 1 DAY
ORDER BY created_at DESC;

-- Violations by type
SELECT violation_type, COUNT(*) as count
FROM exam_violations
GROUP BY violation_type
ORDER BY count DESC;
```

### Check Encrypted Data
```php
// In tinker
php artisan tinker

// Check if data is encrypted
$answer = Answer::first();
dd($answer->getAttributes()['answer_text']); // Encrypted
dd($answer->answer_text);                     // Decrypted
```

---

## 🔐 Security Checklist

### Before Deployment
- [ ] All passwords meet complexity requirements
- [ ] File upload limits configured
- [ ] API validation tested
- [ ] Anti-cheat middleware applied
- [ ] Tests passing
- [ ] Cache cleared and optimized

### Production Settings
```env
APP_ENV=production
APP_DEBUG=false
SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true
```

### Regular Maintenance
- [ ] Review violation logs weekly
- [ ] Update password policies quarterly
- [ ] Test file upload security monthly
- [ ] Audit API access logs monthly

---

## 📞 Support

**Documentation:** `/IMMEDIATE_SECURITY_IMPLEMENTATION.md`  
**Tests:** `/tests/Feature/ImmediateSecurityTest.php`  
**Issues:** Check application logs in `storage/logs/`

---

**Last Updated:** 2025-12-07  
**Version:** 1.0.0  
**Status:** ✅ Production Ready
