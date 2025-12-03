<?php

namespace App\Imports;

use App\Models\Student;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class StudentsImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        return new Student([
            'nisn'          => (string) $row['nisn'],
            'name'          => $row['name'],
            'password'      => Hash::make($row['password']),
            'gender'        => $row['gender'],
            'classroom_id'  => (int) $row['classroom_id'],
        ]);
    }
        
    public function rules(): array
    {
        return [
            'nisn' => 'required|unique:students,nisn',
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:6',
            'gender' => 'required|in:L,P',
            'classroom_id' => 'required|exists:classrooms,id',
        ];
    }
}
