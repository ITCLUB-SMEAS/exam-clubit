<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use SoftDeletes;

    public const TYPE_MULTIPLE_CHOICE_SINGLE = "multiple_choice_single";
    public const TYPE_MULTIPLE_CHOICE_MULTIPLE = "multiple_choice_multiple";
    public const TYPE_SHORT_ANSWER = "short_answer";
    public const TYPE_ESSAY = "essay";
    public const TYPE_TRUE_FALSE = "true_false";
    public const TYPE_MATCHING = "matching";

    /**
     * fillable
     *
     * @var array
     */
    protected $fillable = [
        "exam_id",
        "question",
        "question_type",
        "points",
        "option_1",
        "option_2",
        "option_3",
        "option_4",
        "option_5",
        "answer",
        "correct_answers",
        "matching_pairs",
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        "points" => "float",
        "correct_answers" => "array",
        "matching_pairs" => "array",
    ];

    /**
     * exam
     *
     * @return void
     */
    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function versions()
    {
        return $this->hasMany(QuestionVersion::class)->orderBy('version_number', 'desc');
    }

    public function createVersion(?int $userId = null, ?string $reason = null): QuestionVersion
    {
        $versionNumber = ($this->current_version ?? 0) + 1;

        $version = QuestionVersion::create([
            'question_id' => $this->id,
            'user_id' => $userId,
            'version_number' => $versionNumber,
            'data' => $this->only([
                'question', 'question_type', 'points',
                'option_1', 'option_2', 'option_3', 'option_4', 'option_5',
                'answer', 'correct_answers', 'matching_pairs',
            ]),
            'change_reason' => $reason,
        ]);

        $this->update(['current_version' => $versionNumber]);

        return $version;
    }

    public function restoreVersion(int $versionNumber): bool
    {
        $version = $this->versions()->where('version_number', $versionNumber)->first();
        if (!$version) return false;

        $this->update($version->data);
        return true;
    }
}
