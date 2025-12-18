<?php

namespace App\Imports;

use App\Models\Room;
use App\Models\Student;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
    protected $photoErrors = [];

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

        // Handle photo URL
        $photoPath = null;
        if (!empty($row['photo_url']) || !empty($row['photo'])) {
            $photoUrl = $row['photo_url'] ?? $row['photo'] ?? null;
            $photoPath = $this->downloadAndSavePhoto($photoUrl, (string) $row['nisn']);
        }

        return new Student([
            'nisn'          => (string) $row['nisn'],
            'name'          => trim($row['name']),
            'password'      => Hash::make($row['password'] ?? '123456'),
            'gender'        => $row['gender'] ?? 'L',
            'classroom_id'  => (int) ($row['classroom_id'] ?? 1),
            'room_id'       => $roomId ? (int) $roomId : null,
            'photo'         => $photoPath,
        ]);
    }

    /**
     * Download photo from URL and save to storage
     */
    protected function downloadAndSavePhoto(?string $url, string $nisn): ?string
    {
        if (empty($url)) {
            return null;
        }

        try {
            // If it's a local path reference (e.g., just filename), skip download
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                // Assume it's a relative path or filename, try to use as-is
                return null;
            }

            // Download the image
            $response = Http::timeout(10)->get($url);
            
            if (!$response->successful()) {
                $this->photoErrors[] = [
                    'nisn' => $nisn,
                    'url' => $url,
                    'error' => 'Failed to download (HTTP ' . $response->status() . ')',
                ];
                return null;
            }

            // Get content type and determine extension
            $contentType = $response->header('Content-Type');
            $extension = $this->getExtensionFromContentType($contentType);
            
            if (!$extension) {
                // Try to get extension from URL
                $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
                if (!in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $extension = 'jpg'; // Default
                }
            }

            // Generate unique filename
            $filename = 'students/' . $nisn . '_' . Str::random(8) . '.' . $extension;

            // Save to storage
            Storage::disk('public')->put($filename, $response->body());

            return $filename;

        } catch (\Exception $e) {
            $this->photoErrors[] = [
                'nisn' => $nisn,
                'url' => $url,
                'error' => $e->getMessage(),
            ];
            return null;
        }
    }

    /**
     * Get file extension from content type
     */
    protected function getExtensionFromContentType(?string $contentType): ?string
    {
        if (!$contentType) {
            return null;
        }

        $map = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];

        return $map[$contentType] ?? null;
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

    public function getPhotoErrors(): array
    {
        return $this->photoErrors;
    }
}
