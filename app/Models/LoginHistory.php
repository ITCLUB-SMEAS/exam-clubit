<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class LoginHistory extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'user_type',
        'user_id',
        'ip_address',
        'user_agent',
        'device',
        'browser',
        'platform',
        'status',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public static function record(string $userType, int $userId, string $status, Request $request): self
    {
        $agent = $request->userAgent() ?? '';
        
        return self::create([
            'user_type' => $userType,
            'user_id' => $userId,
            'ip_address' => $request->ip(),
            'user_agent' => substr($agent, 0, 255),
            'device' => self::parseDevice($agent),
            'browser' => self::parseBrowser($agent),
            'platform' => self::parsePlatform($agent),
            'status' => $status,
            'created_at' => now(),
        ]);
    }

    protected static function parseDevice(string $agent): string
    {
        if (preg_match('/Mobile|Android|iPhone|iPad/i', $agent)) return 'Mobile';
        if (preg_match('/Tablet/i', $agent)) return 'Tablet';
        return 'Desktop';
    }

    protected static function parseBrowser(string $agent): string
    {
        if (preg_match('/Chrome/i', $agent)) return 'Chrome';
        if (preg_match('/Firefox/i', $agent)) return 'Firefox';
        if (preg_match('/Safari/i', $agent)) return 'Safari';
        if (preg_match('/Edge/i', $agent)) return 'Edge';
        return 'Other';
    }

    protected static function parsePlatform(string $agent): string
    {
        if (preg_match('/Windows/i', $agent)) return 'Windows';
        if (preg_match('/Mac/i', $agent)) return 'MacOS';
        if (preg_match('/Linux/i', $agent)) return 'Linux';
        if (preg_match('/Android/i', $agent)) return 'Android';
        if (preg_match('/iOS|iPhone|iPad/i', $agent)) return 'iOS';
        return 'Other';
    }

    public function user()
    {
        return $this->user_type === 'admin' 
            ? $this->belongsTo(User::class, 'user_id')
            : $this->belongsTo(Student::class, 'user_id');
    }
}
