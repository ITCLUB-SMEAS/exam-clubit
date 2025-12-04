<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamGroup extends Model
{
    protected $fillable = [
        'exam_id',
        'exam_session_id',
        'student_id',
        'checked_in_at',
        'checkin_method',
        'checkin_ip',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function exam_session()
    {
        return $this->belongsTo(ExamSession::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Check if student has checked in
     */
    public function isCheckedIn(): bool
    {
        return $this->checked_in_at !== null;
    }

    /**
     * Perform check-in
     */
    public function checkIn(string $method, ?string $ip = null): void
    {
        $this->update([
            'checked_in_at' => now(),
            'checkin_method' => $method,
            'checkin_ip' => $ip,
        ]);
    }
}
