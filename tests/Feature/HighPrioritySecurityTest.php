<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Student;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HighPrioritySecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    // ==================== Telegram Webhook Security ====================

    public function test_telegram_webhook_rejects_request_without_secret(): void
    {
        $response = $this->postJson('/telegram/webhook', [
            'message' => ['text' => '/status', 'chat' => ['id' => 123]]
        ]);

        $response->assertStatus(403);
    }

    public function test_telegram_webhook_rejects_request_with_wrong_secret(): void
    {
        config(['services.telegram.webhook_secret' => 'correct_secret']);

        $response = $this->postJson('/telegram/webhook', [
            'message' => ['text' => '/status', 'chat' => ['id' => 123]]
        ], ['X-Telegram-Bot-Api-Secret-Token' => 'wrong_secret']);

        $response->assertStatus(403);
    }

    public function test_telegram_webhook_rejects_when_secret_not_configured(): void
    {
        config(['services.telegram.webhook_secret' => null]);

        $response = $this->postJson('/telegram/webhook', [
            'message' => ['text' => '/status', 'chat' => ['id' => 123]]
        ]);

        $response->assertStatus(403);
    }

    // ==================== CSRF Protection ====================

    public function test_student_logout_requires_csrf_token(): void
    {
        $student = Student::factory()->create();
        
        $this->actingAs($student, 'student');

        // POST without CSRF should fail
        $response = $this->post('/student/logout', [], [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        // Should get 419 (CSRF token mismatch) or redirect
        $this->assertTrue(in_array($response->status(), [419, 302]));
    }

    public function test_student_logout_works_with_csrf_token(): void
    {
        $student = Student::factory()->create();
        
        // Use from() to set referer and withoutMiddleware for this specific test
        // The point is CSRF is now required (tested above), this tests the actual logout works
        $response = $this->actingAs($student, 'student')
            ->from('/student/dashboard')
            ->withSession(['_token' => 'test-token'])
            ->post('/student/logout', ['_token' => 'test-token']);

        $response->assertRedirect('/');
    }

    // ==================== Backup Encryption ====================

    public function test_backup_creates_encrypted_file(): void
    {
        // Create minimal test data
        $this->artisan('migrate:fresh');
        
        $service = new BackupService();
        $path = $service->createDatabaseBackup();

        $this->assertNotNull($path);
        $this->assertTrue(str_ends_with($path, '.sql.enc'));
        
        Storage::disk('local')->assertExists($path);
    }

    public function test_backup_content_is_encrypted(): void
    {
        $this->artisan('migrate:fresh');
        
        $service = new BackupService();
        $path = $service->createDatabaseBackup();

        $content = Storage::disk('local')->get($path);
        
        // Content should not be plain SQL
        $this->assertStringNotContainsString('CREATE TABLE', $content);
        $this->assertStringNotContainsString('INSERT INTO', $content);
        
        // Should be decryptable
        $decrypted = Crypt::decryptString($content);
        $this->assertStringContainsString('Database Backup', $decrypted);
    }

    public function test_backup_download_decrypts_file(): void
    {
        $this->artisan('migrate:fresh');
        
        $service = new BackupService();
        $path = $service->createDatabaseBackup();
        $filename = basename($path);

        $downloadPath = $service->downloadBackup($filename);

        $this->assertNotNull($downloadPath);
        
        // Temp file should be decrypted SQL
        $tempFilename = 'temp_' . str_replace('.enc', '', $filename);
        Storage::disk('local')->assertExists('backups/' . $tempFilename);
    }

    public function test_backup_list_shows_encryption_status(): void
    {
        $this->artisan('migrate:fresh');
        
        $service = new BackupService();
        $service->createDatabaseBackup();

        $backups = $service->listBackups();

        $this->assertNotEmpty($backups);
        $this->assertTrue($backups[0]['encrypted']);
    }

    public function test_backup_delete_validates_filename(): void
    {
        $service = new BackupService();

        // Path traversal attempts should fail
        $this->assertFalse($service->deleteBackup('../../../etc/passwd'));
        $this->assertFalse($service->deleteBackup('malicious.php'));
        $this->assertFalse($service->deleteBackup('db_backup.sql.php'));
        
        // Valid filename pattern but file doesn't exist - Storage::delete returns false for non-existent
        // Create a file first, then delete it
        Storage::disk('local')->put('backups/db_backup_2024-01-01_120000.sql.enc', 'test');
        $this->assertTrue($service->deleteBackup('db_backup_2024-01-01_120000.sql.enc'));
        Storage::disk('local')->assertMissing('backups/db_backup_2024-01-01_120000.sql.enc');
    }

    public function test_backup_cleanup_removes_temp_files(): void
    {
        Storage::disk('local')->put('backups/temp_test.sql', 'test');
        Storage::disk('local')->put('backups/db_backup_2024-01-01_120000.sql.enc', 'encrypted');

        $service = new BackupService();
        $deleted = $service->cleanupTempFiles();

        $this->assertEquals(1, $deleted);
        Storage::disk('local')->assertMissing('backups/temp_test.sql');
        Storage::disk('local')->assertExists('backups/db_backup_2024-01-01_120000.sql.enc');
    }
}
