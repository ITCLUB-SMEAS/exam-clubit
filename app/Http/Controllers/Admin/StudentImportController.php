<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesTransactions;
use App\Http\Controllers\Traits\LogsActivity;
use App\Imports\StudentsImport;
use App\Models\Student;
use App\Services\SecureZipService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Controller for student import operations.
 * Extracted from StudentController for Single Responsibility.
 */
class StudentImportController extends Controller
{
    use HandlesTransactions, LogsActivity;

    /**
     * Show import page.
     */
    public function import()
    {
        return inertia('Admin/Students/Import');
    }

    /**
     * Download Excel template for student import.
     */
    public function downloadTemplate()
    {
        $headers = [
            'nisn',
            'name',
            'password',
            'gender',
            'classroom_id',
            'room_id',
            'photo_url',
        ];

        $example = [
            '1234567890',
            'John Doe',
            '123456',
            'L',
            '1',
            'auto',
            'https://example.com/photo.jpg',
        ];

        $callback = function () use ($headers, $example) {
            $file = fopen('php://output', 'w');

            // Add UTF-8 BOM for proper Excel encoding
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Write header
            fputcsv($file, $headers);

            // Write example row
            fputcsv($file, $example);

            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template_siswa.csv"',
        ]);
    }

    /**
     * Process Excel/CSV import.
     */
    public function storeImport(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,xls,xlsx|max:10240',
        ]);

        $import = new StudentsImport;
        Excel::import($import, $request->file('file'));

        // Check for duplicates and photo errors
        $duplicates = $import->getSkippedDuplicates();
        $photoErrors = $import->getPhotoErrors();

        $messages = [];

        if (count($duplicates) > 0) {
            $duplicateList = collect($duplicates)
                ->map(fn ($d) => "Baris {$d['row']}: {$d['nisn']} - {$d['name']}")
                ->implode(', ');
            $messages[] = count($duplicates).' siswa dilewati karena NISN sudah ada: '.$duplicateList;
        }

        if (count($photoErrors) > 0) {
            $photoErrorList = collect($photoErrors)
                ->map(fn ($p) => "{$p['nisn']}: {$p['error']}")
                ->take(5)
                ->implode(', ');
            $messages[] = count($photoErrors).' foto gagal didownload: '.$photoErrorList;
        }

        if (count($messages) > 0) {
            return redirect()->route('admin.students.index')
                ->with('warning', 'Import selesai. '.implode('. ', $messages));
        }

        return redirect()->route('admin.students.index')
            ->with('success', 'Import siswa berhasil!');
    }

    /**
     * Import students from ZIP file containing Excel + photos.
     */
    public function storeImportZip(Request $request, SecureZipService $zipService)
    {
        $request->validate([
            'file' => 'required|file|mimes:zip|max:51200', // 50MB max
        ]);

        // Pre-validate ZIP for security threats
        $zipValidation = $zipService->validateUploadedZip($request->file('file'));
        if (! $zipValidation['valid']) {
            return back()->with('error', 'File ZIP tidak aman: '.$zipValidation['reason']);
        }

        return $this->safeExecute(function () use ($request, $zipService) {
            $zipPath = $request->file('file')->getRealPath();
            $tempDir = storage_path('app/temp/import_'.uniqid());

            // Use secure extraction with limits
            try {
                $extraction = $zipService
                    ->setMaxFiles(500)
                    ->setMaxExtractedSize(200 * 1024 * 1024) // 200MB max extracted
                    ->extractSafely($zipPath, $tempDir, ['xlsx', 'xls', 'csv', 'jpg', 'jpeg', 'png', 'webp']);
            } catch (\RuntimeException $e) {
                $this->cleanupTempDir($tempDir);

                return back()->with('error', $e->getMessage());
            }

            // Find Excel/CSV file
            $dataFile = $this->findDataFile($tempDir);

            if (! $dataFile) {
                $this->cleanupTempDir($tempDir);

                return back()->with('error', 'File Excel/CSV tidak ditemukan dalam ZIP. Harap beri nama: data.xlsx, data.csv, students.xlsx, atau siswa.xlsx');
            }

            // Find photos directory
            $photosDir = $this->findPhotosDir($tempDir);

            // Import students from Excel
            $import = new StudentsImport;
            Excel::import($import, $dataFile);

            $duplicates = $import->getSkippedDuplicates();
            $photosMatched = 0;
            $photosFailed = [];

            // Match photos to students
            if ($photosDir) {
                $result = $this->matchPhotosToStudents($photosDir);
                $photosMatched = $result['matched'];
                $photosFailed = $result['failed'];
            }

            // Cleanup temp directory
            $this->cleanupTempDir($tempDir);

            // Build result message
            $messages = [];

            if (count($duplicates) > 0) {
                $messages[] = count($duplicates).' siswa dilewati (NISN sudah ada)';
            }

            if ($photosMatched > 0) {
                $messages[] = $photosMatched.' foto berhasil dipasangkan';
            }

            if (count($photosFailed) > 0) {
                $failedList = implode(', ', array_slice($photosFailed, 0, 5));
                if (count($photosFailed) > 5) {
                    $failedList .= '...';
                }
                $messages[] = count($photosFailed).' foto gagal: '.$failedList;
            }

            $resultMessage = 'Import ZIP selesai.';
            if (! empty($messages)) {
                $resultMessage .= ' '.implode('. ', $messages);
            }

            return redirect()->route('admin.students.index')
                ->with('success', $resultMessage);

        }, 'Gagal memproses file ZIP. Pastikan format sesuai.');
    }

    /**
     * Bulk photo upload page.
     */
    public function bulkPhotoUpload()
    {
        return inertia('Admin/Students/BulkPhotoUpload');
    }

    /**
     * Process bulk photo upload (ZIP file with NISN-named photos).
     */
    public function processBulkPhotoUpload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:zip|max:51200', // 50MB max
        ]);

        return $this->safeExecute(function () use ($request) {
            $zip = new \ZipArchive;
            $zipPath = $request->file('file')->getRealPath();

            if ($zip->open($zipPath) !== true) {
                return back()->with('error', 'Gagal membuka file ZIP.');
            }

            $result = $this->processZipPhotos($zip);
            $zip->close();

            $message = "{$result['uploaded']} foto berhasil diupload.";
            if (count($result['failed']) > 0) {
                $message .= ' '.count($result['failed']).' gagal: '.implode(', ', array_slice($result['failed'], 0, 5));
                if (count($result['failed']) > 5) {
                    $message .= '...';
                }
            }

            return back()->with('success', $message);
        }, 'Gagal memproses file ZIP. Silakan coba lagi.');
    }

    /**
     * Find data file in extracted directory.
     */
    private function findDataFile(string $tempDir): ?string
    {
        $possibleNames = ['data.xlsx', 'data.xls', 'data.csv', 'students.xlsx', 'students.xls', 'students.csv', 'siswa.xlsx', 'siswa.xls', 'siswa.csv'];

        foreach ($possibleNames as $name) {
            if (file_exists($tempDir.'/'.$name)) {
                return $tempDir.'/'.$name;
            }
        }

        // Also search recursively one level
        $files = glob($tempDir.'/*/*.{xlsx,xls,csv}', GLOB_BRACE);
        if (! empty($files)) {
            return $files[0];
        }

        return null;
    }

    /**
     * Find photos directory in extracted directory.
     */
    private function findPhotosDir(string $tempDir): ?string
    {
        $possiblePhotoDirs = ['photos', 'photo', 'foto', 'images', 'img'];

        foreach ($possiblePhotoDirs as $dir) {
            if (is_dir($tempDir.'/'.$dir)) {
                return $tempDir.'/'.$dir;
            }
        }

        // If no photos folder, check root for image files
        if (glob($tempDir.'/*.{jpg,jpeg,png,webp}', GLOB_BRACE)) {
            return $tempDir;
        }

        return null;
    }

    /**
     * Match photos to students by NISN filename.
     */
    private function matchPhotosToStudents(string $photosDir): array
    {
        $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
        $photoFiles = glob($photosDir.'/*.{'.implode(',', $allowedExt).'}', GLOB_BRACE);

        $matched = 0;
        $failed = [];

        foreach ($photoFiles as $photoPath) {
            $filename = basename($photoPath);
            $nisn = pathinfo($filename, PATHINFO_FILENAME);
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            $student = Student::where('nisn', $nisn)->first();

            if (! $student) {
                $failed[] = "{$nisn} - Siswa tidak ditemukan";

                continue;
            }

            // Save photo
            $content = file_get_contents($photoPath);
            $newFilename = "students/{$nisn}.{$ext}";

            Storage::disk('public')->put($newFilename, $content);

            // Delete old photo if exists
            if ($student->photo && Storage::disk('public')->exists($student->photo)) {
                Storage::disk('public')->delete($student->photo);
            }

            $student->update(['photo' => $newFilename]);
            $matched++;
        }

        return ['matched' => $matched, 'failed' => $failed];
    }

    /**
     * Process photos from ZIP archive.
     */
    private function processZipPhotos(\ZipArchive $zip): array
    {
        $uploaded = 0;
        $failed = [];
        $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $nisn = pathinfo($filename, PATHINFO_FILENAME);

            // Skip directories and non-image files
            if (! in_array($ext, $allowedExt)) {
                continue;
            }

            $student = Student::where('nisn', $nisn)->first();
            if (! $student) {
                $failed[] = "{$nisn} - Siswa tidak ditemukan";

                continue;
            }

            // Extract and save photo
            $content = $zip->getFromIndex($i);
            $newFilename = "students/{$nisn}.{$ext}";

            Storage::disk('public')->put($newFilename, $content);

            // Delete old photo if exists
            if ($student->photo && Storage::disk('public')->exists($student->photo)) {
                Storage::disk('public')->delete($student->photo);
            }

            $student->update(['photo' => $newFilename]);
            $uploaded++;
        }

        return ['uploaded' => $uploaded, 'failed' => $failed];
    }

    /**
     * Cleanup temporary directory.
     */
    private function cleanupTempDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        rmdir($dir);
    }
}
