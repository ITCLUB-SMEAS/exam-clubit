<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ExamSession extends Model
{
    protected $fillable = [
        'exam_id',
        'title',
        'start_time',
        'end_time',
        'access_token',
        'qr_secret',
        'require_attendance',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'require_attendance' => 'boolean',
    ];

    public function exam_groups()
    {
        return $this->hasMany(ExamGroup::class);
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * Generate new access token
     */
    public function generateAccessToken(): string
    {
        $this->access_token = strtoupper(Str::random(6));
        $this->save();
        return $this->access_token;
    }

    /**
     * Generate QR secret for rotating QR codes
     */
    public function generateQrSecret(): string
    {
        $this->qr_secret = Str::random(32);
        $this->save();
        return $this->qr_secret;
    }

    /**
     * Get current QR code data (rotates every 30 seconds)
     */
    public function getCurrentQrCode(): string
    {
        if (!$this->qr_secret) {
            $this->generateQrSecret();
        }
        
        $timestamp = floor(time() / 30); // Changes every 30 seconds
        return hash('sha256', $this->qr_secret . $timestamp . $this->id);
    }

    /**
     * Validate QR code (check current and previous interval)
     */
    public function validateQrCode(string $code): bool
    {
        if (!$this->qr_secret) return false;
        
        $currentTimestamp = floor(time() / 30);
        
        // Check current and previous interval (60 second window)
        for ($i = 0; $i <= 1; $i++) {
            $validCode = hash('sha256', $this->qr_secret . ($currentTimestamp - $i) . $this->id);
            if (hash_equals($validCode, $code)) {
                return true;
            }
        }
        
        return false;
    }
}
