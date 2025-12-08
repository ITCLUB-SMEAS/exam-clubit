<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ImportantFeaturesTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function health_check_endpoint_returns_status()
    {
        $response = $this->get('/health');
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'timestamp',
            'checks' => ['database', 'cache', 'storage']
        ]);
    }

    /** @test */
    public function health_check_validates_database_connection()
    {
        $response = $this->get('/health');
        $data = $response->json();
        
        $this->assertTrue($data['checks']['database']);
    }

    /** @test */
    public function automated_backup_command_exists()
    {
        $this->assertTrue(
            class_exists(\App\Console\Commands\AutomatedBackup::class),
            'AutomatedBackup command should exist'
        );
    }

    /** @test */
    public function backup_is_scheduled()
    {
        $schedule = app()->make(\Illuminate\Console\Scheduling\Schedule::class);
        $events = collect($schedule->events());
        
        $backupEvent = $events->first(function ($event) {
            return str_contains($event->command ?? '', 'backup:automated');
        });
        
        $this->assertNotNull($backupEvent, 'Backup should be scheduled');
    }

    /** @test */
    public function logs_activity_trait_exists()
    {
        $this->assertTrue(
            trait_exists(\App\Http\Controllers\Traits\LogsActivity::class),
            'LogsActivity trait should exist'
        );
    }

    /** @test */
    public function student_controller_uses_logs_activity_trait()
    {
        $traits = class_uses_recursive(\App\Http\Controllers\Admin\StudentController::class);
        
        $this->assertContains(
            \App\Http\Controllers\Traits\LogsActivity::class,
            $traits,
            'StudentController should use LogsActivity trait'
        );
    }

    /** @test */
    public function student_form_requests_exist()
    {
        $this->assertTrue(
            class_exists(\App\Http\Requests\StoreStudentRequest::class),
            'StoreStudentRequest should exist'
        );
        
        $this->assertTrue(
            class_exists(\App\Http\Requests\UpdateStudentRequest::class),
            'UpdateStudentRequest should exist'
        );
    }

    /** @test */
    public function store_student_request_has_validation_rules()
    {
        $request = new \App\Http\Requests\StoreStudentRequest();
        $rules = $request->rules();
        
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('nisn', $rules);
        $this->assertArrayHasKey('password', $rules);
        $this->assertArrayHasKey('classroom_id', $rules);
    }
}
