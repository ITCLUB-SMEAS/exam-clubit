<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Classroom extends Model
{
    protected $fillable = ['title'];

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function exams()
    {
        return $this->hasMany(Exam::class);
    }
}
