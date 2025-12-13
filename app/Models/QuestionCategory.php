<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionCategory extends Model
{
    protected $fillable = ['name', 'description', 'lesson_id'];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function questionBanks()
    {
        return $this->hasMany(QuestionBank::class, 'category_id');
    }

    // Alias for withCount
    public function questions()
    {
        return $this->hasMany(QuestionBank::class, 'category_id');
    }
}
