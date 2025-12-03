<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $fillable = ['title'];

    public function exams()
    {
        return $this->hasMany(Exam::class);
    }
}
