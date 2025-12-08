<?php

namespace App\Models;

use App\Models\Traits\HasEncryptedAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Answer extends Model
{
    use HasEncryptedAttributes, SoftDeletes;

    /**
     * fillable
     *
     * @var array
     */
    protected $fillable = [
        "exam_id",
        "exam_session_id",
        "question_id",
        "student_id",
        "question_order",
        "answer_order",
        "answer",
        "is_correct",
        "answer_text",
        "answer_options",
        "matching_answers",
        "points_awarded",
        "needs_manual_review",
    ];

    /**
     * Encrypted attributes
     *
     * @var array
     */
    protected $encrypted = [
        "answer_text",
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        "answer_options" => "array",
        "matching_answers" => "array",
        "points_awarded" => "float",
        "needs_manual_review" => "boolean",
    ];

    /**
     * Guarded attributes
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * question
     *
     * @return void
     */
    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * student
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
