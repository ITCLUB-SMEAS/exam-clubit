<?php

namespace App\Imports;

use App\Models\Room;
use App\Models\Student;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\WithValidation;

class StudentsImport implements ToModel, WithHeadingRow, SkipsEmptyRows, SkipsOnError, WithValidation
{
    use SkipsErrors;

    protected $skippedDuplicates = [];
    protected $rowNumber = 1;

    public function model(array $row)
    {
        $this->rowNumber++;

        // Skip if nisn or name is blank
        if (empty($row['nisn']) || empty($row['name'])) {
            return null;
        }

        // Track duplicate NISN
        if (Student::where('nisn', (string) $row['nisn'])->exists()) {
            $this->skippedDuplicates[] = [
                'row' => $this->rowNumber,
                'nisn' => $row['nisn'],
                'name' => $row['name'],
            ];
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

    public function rules(): array
    {
        return [
            'nisn' => 'required',
            'name' => 'required',
        ];
    }

    public function getSkippedDuplicates(): array
    {
        return $this->skippedDuplicates;
    }
}
