<?php

namespace Database\Seeders;

use App\Models\Student;
use App\Models\Classroom;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure we have at least one classroom
        $classroom = Classroom::first();

        if (!$classroom) {
            $classroom = Classroom::create([
                'title' => 'Kelas 12 IPA 1',
            ]);
        }

        // Create test students with hashed passwords
        $students = [
            [
                'classroom_id' => $classroom->id,
                'nisn' => '1234567890',
                'name' => 'Budi Santoso',
                'password' => Hash::make('password123'),
                'gender' => 'L',
            ],
            [
                'classroom_id' => $classroom->id,
                'nisn' => '1234567891',
                'name' => 'Siti Rahayu',
                'password' => Hash::make('password123'),
                'gender' => 'P',
            ],
            [
                'classroom_id' => $classroom->id,
                'nisn' => '1234567892',
                'name' => 'Ahmad Fauzi',
                'password' => Hash::make('password123'),
                'gender' => 'L',
            ],
            [
                'classroom_id' => $classroom->id,
                'nisn' => '0000000001',
                'name' => 'Test Student',
                'password' => Hash::make('test123'),
                'gender' => 'L',
            ],
        ];

        foreach ($students as $studentData) {
            // Check if student with this NISN already exists
            $existingStudent = Student::where('nisn', $studentData['nisn'])->first();

            if (!$existingStudent) {
                Student::create($studentData);
                $this->command->info("Created student: {$studentData['name']} (NISN: {$studentData['nisn']})");
            } else {
                $this->command->warn("Student with NISN {$studentData['nisn']} already exists, skipping...");
            }
        }

        $this->command->info('');
        $this->command->info('===========================================');
        $this->command->info('  Test Accounts Created:');
        $this->command->info('===========================================');
        $this->command->info('  NISN: 1234567890 | Password: password123');
        $this->command->info('  NISN: 1234567891 | Password: password123');
        $this->command->info('  NISN: 1234567892 | Password: password123');
        $this->command->info('  NISN: 0000000001 | Password: test123');
        $this->command->info('===========================================');
    }
}
