<?php

namespace App\Console\Commands;

use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class HashExistingPasswords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'students:hash-passwords
                            {--force : Force the operation without confirmation}
                            {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hash existing plain-text student passwords in the database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('===========================================');
        $this->info('  Student Password Hashing Tool');
        $this->info('===========================================');
        $this->newLine();

        // Get all students
        $students = Student::all();
        $totalStudents = $students->count();

        if ($totalStudents === 0) {
            $this->warn('No students found in the database.');
            return Command::SUCCESS;
        }

        $this->info("Found {$totalStudents} students in the database.");
        $this->newLine();

        // Check for dry run
        $isDryRun = $this->option('dry-run');
        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made.');
            $this->newLine();
        }

        // Confirm action
        if (!$this->option('force') && !$isDryRun) {
            if (!$this->confirm('This will hash all plain-text passwords. Do you want to continue?')) {
                $this->info('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        $updated = 0;
        $skipped = 0;
        $errors = 0;

        $this->output->progressStart($totalStudents);

        foreach ($students as $student) {
            try {
                // Check if password is already hashed (bcrypt hashes start with $2y$)
                if ($this->isAlreadyHashed($student->password)) {
                    $skipped++;
                    $this->output->progressAdvance();
                    continue;
                }

                if (!$isDryRun) {
                    // Get the raw password and hash it
                    $plainPassword = $student->getRawOriginal('password');

                    // Update directly in database to avoid model casting
                    Student::where('id', $student->id)->update([
                        'password' => Hash::make($plainPassword)
                    ]);
                }

                $updated++;
            } catch (\Exception $e) {
                $errors++;
                $this->newLine();
                $this->error("Error updating student ID {$student->id}: {$e->getMessage()}");
            }

            $this->output->progressAdvance();
        }

        $this->output->progressFinish();
        $this->newLine();

        // Summary
        $this->info('===========================================');
        $this->info('  Summary');
        $this->info('===========================================');
        $this->table(
            ['Status', 'Count'],
            [
                ['Total Students', $totalStudents],
                ['Passwords Updated', $updated],
                ['Already Hashed (Skipped)', $skipped],
                ['Errors', $errors],
            ]
        );

        if ($isDryRun) {
            $this->newLine();
            $this->warn('This was a dry run. Run without --dry-run to apply changes.');
        }

        if ($errors > 0) {
            $this->newLine();
            $this->error("Completed with {$errors} errors.");
            return Command::FAILURE;
        }

        $this->newLine();
        $this->info('Password hashing completed successfully!');

        return Command::SUCCESS;
    }

    /**
     * Check if a password is already hashed
     *
     * @param string $password
     * @return bool
     */
    protected function isAlreadyHashed(string $password): bool
    {
        // Bcrypt hashes start with $2y$ or $2a$ and are 60 characters
        // Argon2 hashes start with $argon2
        return (
            strlen($password) >= 60 &&
            (
                str_starts_with($password, '$2y$') ||
                str_starts_with($password, '$2a$') ||
                str_starts_with($password, '$2b$') ||
                str_starts_with($password, '$argon2')
            )
        );
    }
}
