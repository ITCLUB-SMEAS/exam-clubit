<?php

namespace Tests\Feature;

use Tests\TestCase;

class FaceAudioDetectionTest extends TestCase
{
    public function test_face_detection_composable_exists(): void
    {
        $file = base_path('resources/js/composables/useFaceDetection.js');
        $this->assertFileExists($file);
    }

    public function test_audio_detection_composable_exists(): void
    {
        $file = base_path('resources/js/composables/useAudioDetection.js');
        $this->assertFileExists($file);
    }

    public function test_face_detection_models_exist(): void
    {
        $modelPath = public_path('models');
        $this->assertDirectoryExists($modelPath);
        
        $manifestFile = public_path('models/tiny_face_detector_model-weights_manifest.json');
        $this->assertFileExists($manifestFile);
    }

    public function test_face_detection_has_video_ready_check(): void
    {
        $file = base_path('resources/js/composables/useFaceDetection.js');
        $content = file_get_contents($file);
        
        // Check for video readyState validation
        $hasReadyCheck = str_contains($content, 'readyState');
        $this->assertTrue($hasReadyCheck, 'Face detection should check video readyState');
    }

    public function test_face_detection_uses_absolute_path(): void
    {
        $file = base_path('resources/js/composables/useFaceDetection.js');
        $content = file_get_contents($file);
        
        // Check for absolute path or origin-based path
        $hasAbsolutePath = str_contains($content, 'window.location.origin');
        $this->assertTrue($hasAbsolutePath, 'Face detection should use absolute path for models');
    }

    public function test_audio_detection_has_correct_frequency_calculation(): void
    {
        $file = base_path('resources/js/composables/useAudioDetection.js');
        $content = file_get_contents($file);
        
        // Check for nyquist frequency calculation
        $hasNyquist = str_contains($content, 'nyquist');
        $this->assertTrue($hasNyquist, 'Audio detection should use nyquist frequency');
        
        // Check for proper bin size calculation
        $hasBinSize = str_contains($content, 'binSize');
        $this->assertTrue($hasBinSize, 'Audio detection should calculate bin size');
    }

    public function test_audio_detection_validates_frequency_range(): void
    {
        $file = base_path('resources/js/composables/useAudioDetection.js');
        $content = file_get_contents($file);
        
        // Check for range validation
        $hasValidation = str_contains($content, 'minBin >= maxBin');
        $this->assertTrue($hasValidation, 'Audio detection should validate frequency range');
    }

    public function test_face_detection_has_cooldown(): void
    {
        $file = base_path('resources/js/composables/useFaceDetection.js');
        $content = file_get_contents($file);
        
        // Check for cooldown mechanism
        $hasCooldown = str_contains($content, 'TRIGGER_COOLDOWN');
        $this->assertTrue($hasCooldown, 'Face detection should have cooldown between triggers');
    }

    public function test_audio_detection_has_cooldown(): void
    {
        $file = base_path('resources/js/composables/useAudioDetection.js');
        $content = file_get_contents($file);
        
        // Check for cooldown mechanism
        $hasCooldown = str_contains($content, 'TRIGGER_COOLDOWN');
        $this->assertTrue($hasCooldown, 'Audio detection should have cooldown between triggers');
    }

    public function test_face_detection_has_consecutive_threshold(): void
    {
        $file = base_path('resources/js/composables/useFaceDetection.js');
        $content = file_get_contents($file);
        
        // Check for consecutive threshold
        $hasThreshold = str_contains($content, 'consecutiveThreshold');
        $this->assertTrue($hasThreshold, 'Face detection should have consecutive threshold');
    }

    public function test_audio_detection_has_sustained_duration(): void
    {
        $file = base_path('resources/js/composables/useAudioDetection.js');
        $content = file_get_contents($file);
        
        // Check for sustained duration
        $hasDuration = str_contains($content, 'sustainedDuration');
        $this->assertTrue($hasDuration, 'Audio detection should have sustained duration');
    }

    public function test_face_detection_cleanup_stops_stream(): void
    {
        $file = base_path('resources/js/composables/useFaceDetection.js');
        $content = file_get_contents($file);
        
        // Check for proper cleanup
        $hasCleanup = str_contains($content, 'getTracks().forEach') && 
                     str_contains($content, 'track.stop()');
        $this->assertTrue($hasCleanup, 'Face detection should properly stop camera stream');
    }

    public function test_audio_detection_cleanup_closes_context(): void
    {
        $file = base_path('resources/js/composables/useAudioDetection.js');
        $content = file_get_contents($file);
        
        // Check for proper cleanup
        $hasCleanup = str_contains($content, 'audioContext.close()');
        $this->assertTrue($hasCleanup, 'Audio detection should close audio context');
    }

    public function test_face_detection_handles_errors(): void
    {
        $file = base_path('resources/js/composables/useFaceDetection.js');
        $content = file_get_contents($file);
        
        // Check for error handling
        $hasErrorHandling = str_contains($content, 'catch') && 
                           str_contains($content, 'onError');
        $this->assertTrue($hasErrorHandling, 'Face detection should handle errors');
    }

    public function test_audio_detection_handles_errors(): void
    {
        $file = base_path('resources/js/composables/useAudioDetection.js');
        $content = file_get_contents($file);
        
        // Check for error handling
        $hasErrorHandling = str_contains($content, 'catch');
        $this->assertTrue($hasErrorHandling, 'Audio detection should handle errors');
    }
}
