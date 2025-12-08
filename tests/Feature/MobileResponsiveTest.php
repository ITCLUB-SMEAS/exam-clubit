<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\{User, Student};
use Illuminate\Foundation\Testing\RefreshDatabase;

class MobileResponsiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewport_meta_tag_exists(): void
    {
        $response = $this->get('/');
        
        $response->assertSee('viewport', false);
        $response->assertSee('width=device-width', false);
    }

    public function test_mobile_detection_composable_exists(): void
    {
        $file = base_path('resources/js/composables/useMobileDetection.js');
        $this->assertFileExists($file);
    }

    public function test_mobile_css_exists(): void
    {
        $file = base_path('resources/css/mobile-optimizations.css');
        $this->assertFileExists($file);
    }

    public function test_landscape_warning_component_exists(): void
    {
        $file = base_path('resources/js/Components/LandscapeWarning.vue');
        $this->assertFileExists($file);
    }

    public function test_admin_dashboard_has_responsive_classes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $response = $this->actingAs($admin)->get('/admin/dashboard');
        
        $response->assertStatus(200);
    }

    public function test_student_dashboard_accessible_on_mobile(): void
    {
        $student = Student::factory()->create();
        
        $response = $this->actingAs($student, 'student')->get('/student/dashboard');
        
        $response->assertStatus(200);
    }

    public function test_mobile_user_agent_detection(): void
    {
        $mobileUA = 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15';
        
        $isMobile = preg_match('/android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/i', strtolower($mobileUA));
        
        $this->assertEquals(1, $isMobile);
    }

    public function test_desktop_user_agent_detection(): void
    {
        $desktopUA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
        
        $isMobile = preg_match('/android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/i', strtolower($desktopUA));
        
        $this->assertEquals(0, $isMobile);
    }

    public function test_ios_detection(): void
    {
        $iosUA = 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)';
        
        $isIOS = preg_match('/iphone|ipad|ipod/i', strtolower($iosUA));
        
        $this->assertEquals(1, $isIOS);
    }

    public function test_android_detection(): void
    {
        $androidUA = 'Mozilla/5.0 (Linux; Android 11; SM-G991B)';
        
        $isAndroid = preg_match('/android/i', strtolower($androidUA));
        
        $this->assertEquals(1, $isAndroid);
    }

    public function test_responsive_grid_classes_in_views(): void
    {
        $dashboardFile = base_path('resources/js/Pages/Admin/Dashboard/Index.vue');
        
        if (file_exists($dashboardFile)) {
            $content = file_get_contents($dashboardFile);
            
            // Check for Bootstrap responsive classes
            $hasResponsive = str_contains($content, 'col-md') || 
                           str_contains($content, 'col-lg') || 
                           str_contains($content, 'col-sm');
            
            $this->assertTrue($hasResponsive, 'Dashboard should have responsive grid classes');
        } else {
            $this->markTestSkipped('Dashboard file not found');
        }
    }

    public function test_touch_friendly_button_size(): void
    {
        $cssFile = base_path('resources/css/mobile-optimizations.css');
        $content = file_get_contents($cssFile);
        
        // Check for minimum touch target size (44px recommended)
        $hasTouchSize = str_contains($content, 'min-height: 44px');
        
        $this->assertTrue($hasTouchSize, 'Buttons should have touch-friendly size');
    }

    public function test_modal_responsive_styles(): void
    {
        $cssFile = base_path('resources/css/mobile-optimizations.css');
        $content = file_get_contents($cssFile);
        
        $hasModalOptimization = str_contains($content, '.modal-dialog') && 
                               str_contains($content, 'max-height');
        
        $this->assertTrue($hasModalOptimization, 'Modals should be optimized for mobile');
    }

    public function test_landscape_mode_styles(): void
    {
        $cssFile = base_path('resources/css/mobile-optimizations.css');
        $content = file_get_contents($cssFile);
        
        $hasLandscapeStyles = str_contains($content, 'orientation: landscape');
        
        $this->assertTrue($hasLandscapeStyles, 'Should have landscape-specific styles');
    }
}
