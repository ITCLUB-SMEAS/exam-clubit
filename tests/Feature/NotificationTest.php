<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\User;
use App\Models\Student;
use App\Models\Classroom;
use App\Models\Lesson;
use App\Notifications\ExamViolationNotification;
use App\Notifications\ExamSubmittedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_can_view_notifications_page(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/notifications');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Admin/Notifications/Index'));
    }

    public function test_admin_can_get_unread_notifications(): void
    {
        // Create a notification
        $this->admin->notify(new ExamViolationNotification('Test Student', 'Test Exam', 'Tab Switch', 2));

        $response = $this->actingAs($this->admin)->get('/admin/notifications/unread');
        $response->assertStatus(200);
        $response->assertJsonStructure(['notifications', 'count']);
        $response->assertJson(['count' => 1]);
    }

    public function test_admin_can_mark_notification_as_read(): void
    {
        $this->admin->notify(new ExamViolationNotification('Test Student', 'Test Exam', 'Tab Switch', 2));
        $notification = $this->admin->unreadNotifications->first();

        $response = $this->actingAs($this->admin)->post('/admin/notifications/mark-read', [
            'id' => $notification->id,
        ]);

        $response->assertJson(['success' => true]);
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_admin_can_mark_all_notifications_as_read(): void
    {
        $this->admin->notify(new ExamViolationNotification('Student 1', 'Exam 1', 'Tab Switch', 1));
        $this->admin->notify(new ExamViolationNotification('Student 2', 'Exam 2', 'Copy Paste', 2));

        $this->assertEquals(2, $this->admin->unreadNotifications->count());

        $response = $this->actingAs($this->admin)->post('/admin/notifications/mark-read');
        $response->assertJson(['success' => true]);

        $this->assertEquals(0, $this->admin->fresh()->unreadNotifications->count());
    }

    public function test_admin_can_delete_notification(): void
    {
        $this->admin->notify(new ExamViolationNotification('Test Student', 'Test Exam', 'Tab Switch', 2));
        $notification = $this->admin->notifications->first();

        $response = $this->actingAs($this->admin)->delete("/admin/notifications/{$notification->id}");
        $response->assertRedirect();

        $this->assertNull($this->admin->notifications()->find($notification->id));
    }

    public function test_admin_can_delete_all_notifications(): void
    {
        $this->admin->notify(new ExamViolationNotification('Student 1', 'Exam 1', 'Tab Switch', 1));
        $this->admin->notify(new ExamSubmittedNotification('Student 2', 'Exam 2', 85.5, true));

        $this->assertEquals(2, $this->admin->notifications->count());

        $response = $this->actingAs($this->admin)->delete('/admin/notifications');
        $response->assertRedirect();

        $this->assertEquals(0, $this->admin->fresh()->notifications->count());
    }

    public function test_exam_violation_notification_has_correct_data(): void
    {
        $notification = new ExamViolationNotification('Budi', 'UTS Matematika', 'Tab Switch', 3);
        $data = $notification->toArray($this->admin);

        $this->assertEquals('violation', $data['type']);
        $this->assertEquals('danger', $data['color']);
        $this->assertStringContains('Budi', $data['message']);
        $this->assertStringContains('UTS Matematika', $data['message']);
    }

    public function test_exam_submitted_notification_has_correct_data(): void
    {
        $notification = new ExamSubmittedNotification('Budi', 'UTS Matematika', 85.5, true);
        $data = $notification->toArray($this->admin);

        $this->assertEquals('submitted', $data['type']);
        $this->assertEquals('success', $data['color']);
        $this->assertStringContains('LULUS', $data['message']);
    }

    protected function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertTrue(str_contains($haystack, $needle), "Failed asserting that '$haystack' contains '$needle'");
    }
}
