<?php

namespace App\Imports;

use App\Models\Room;
use App\Models\Student;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class StudentsImport implements ToModel, WithHeadingRow, SkipsEmptyRows
{
    public function model(array $row)
    {
        // Skip if nisn or name is blank
        if (empty($row['nisn']) || empty($row['name'])) {
            return null;
        }

        // Skip if nisn already exists
        if (Student::where('nisn', (string) $row['nisn'])->exists()) {
            return null;
        }

        // Auto-assign room if empty or 'auto'
        $roomId = $row['room_id'] ?? null;
        if (empty($roomId) || strtolower($roomId) === 'auto') {
            $room = Room::getRandomAvailable();
            $roomId = $room?->id;
        }

        return new Student([
            'nisn'          => (string) $row['nisn'],
            'name'          => trim($row['name']),
            'password'      => Hash::make($row['password'] ?? '123456'),
            'gender'        => $row['gender'] ?? 'L',
            'classroom_id'  => (int) ($row['classroom_id'] ?? 1),
            'room_id'       => $roomId ? (int) $roomId : null,
        ]);
    }
}
