<?php

namespace App\Imports;

use App\Models\Room;
use App\Models\Student;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class StudentsImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        // Auto-assign room if empty or 'auto'
        $roomId = $row['room_id'] ?? null;
        if (empty($roomId) || strtolower($roomId) === 'auto') {
            $room = Room::getRandomAvailable();
            $roomId = $room?->id;
        }

        return new Student([
            'nisn'          => (string) $row['nisn'],
            'name'          => $row['name'],
            'password'      => Hash::make($row['password']),
            'gender'        => $row['gender'],
            'classroom_id'  => (int) $row['classroom_id'],
            'room_id'       => $roomId ? (int) $roomId : null,
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
            'room_id' => 'nullable',
        ];
    }
}
