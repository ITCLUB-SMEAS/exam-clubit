<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionBank extends Model
{
    protected $fillable = [
        'category_id',
        'question',
        'question_type',
        'points',
        'option_1',
        'option_2',
        'option_3',
        'option_4',
        'option_5',
        'answer',
        'correct_answers',
        'matching_pairs',
        'tags',
    ];

    protected $casts = [
        'points' => 'float',
        'correct_answers' => 'array',
        'matching_pairs' => 'array',
        'tags' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(QuestionCategory::class, 'category_id');
    }
}
