<?php

namespace App\Http\Controllers\Admin;

use App\Models\Student;
use App\Models\Classroom;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Imports\StudentsImport;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesTransactions;
use App\Http\Controllers\Traits\LogsActivity;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;

class StudentController extends Controller
{
    use HandlesTransactions, LogsActivity;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $students = Student::when(request()->q, function($students) {
            $students = $students->where('name', 'like', '%'. request()->q . '%');
        })->with(['classroom', 'room'])->latest()->paginate(10);

        $students->appends(['q' => request()->q]);

        return inertia('Admin/Students/Index', [
            'students' => $students,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return inertia('Admin/Students/Create', [
            'classrooms' => Cache::remember('classrooms_all', 3600, fn() => Classroom::all()),
            'rooms' => Cache::remember('rooms_with_count', 300, fn() => Room::withCount('students')->get()),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'              => 'required|string|max:255',
            'nisn'              => ['required', Rule::unique('students')->whereNull('deleted_at')],
            'gender'            => 'required|string',
            'password'          => 'required|confirmed',
            'classroom_id'      => 'required|exists:classrooms,id',
            'room_id'           => 'nullable|exists:rooms,id',
            'auto_assign_room'  => 'nullable|boolean'
        ]);

        // Auto assign random room if requested
        $roomId = $request->room_id;
        if ($request->auto_assign_room || !$roomId) {
            $room = Room::getRandomAvailable();
            if (!$room) {
                return back()->withErrors(['room_id' => 'Semua ruangan sudah penuh!']);
            }
            $roomId = $room->id;
        }

        Student::create([
            'name'          => $request->name,
            'nisn'          => $request->nisn,
            'gender'        => $request->gender,
            'password'      => $request->password,
            'classroom_id'  => $request->classroom_id,
            'room_id'       => $roomId,
        ]);

        $this->logCreated('student', null, "Created student: {$request->name} ({$request->nisn})");

        return redirect()->route('admin.students.index');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //get student
        $student = Student::findOrFail($id);

        //get classrooms
        $classrooms = Classroom::all();

        //render with inertia
        return inertia('Admin/Students/Edit', [
            'student' => $student,
            'classrooms' => $classrooms,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Student $student)
    {
        //validate request
        $request->validate([
            'name'          => 'required|string|max:255',
            'nisn'          => ['required', Rule::unique('students')->ignore($student->id)->whereNull('deleted_at')],
            'gender'        => 'required|string',
            'classroom_id'  => 'required',
            'password'      => 'confirmed',
            'photo'         => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048'
        ]);

        // Prepare update data
        $updateData = [
            'name'          => $request->name,
            'nisn'          => $request->nisn,
            'gender'        => $request->gender,
            'classroom_id'  => $request->classroom_id
        ];

        // Handle password
        if ($request->password) {
            $updateData['password'] = $request->password;
        }

        // Handle photo upload
        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($student->photo && \Storage::disk('public')->exists($student->photo)) {
                \Storage::disk('public')->delete($student->photo);
            }

            // Store new photo
            $file = $request->file('photo');
            $ext = $file->getClientOriginalExtension();
            $filename = "students/{$request->nisn}." . $ext;
            
            $file->storeAs('students', "{$request->nisn}.{$ext}", 'public');
            $updateData['photo'] = $filename;
        }

        // Handle photo removal
        if ($request->has('remove_photo') && $request->remove_photo == '1') {
            if ($student->photo && \Storage::disk('public')->exists($student->photo)) {
                \Storage::disk('public')->delete($student->photo);
            }
            $updateData['photo'] = null;
        }

        // Update student
        $student->update($updateData);

        $this->logUpdated('student', $student, "Updated student: {$student->name}");

        //redirect
        return redirect()->route('admin.students.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //get student
        $student = Student::findOrFail($id);

        $this->logDeleted('student', $student, "Deleted student: {$student->name} ({$student->nisn})");

        //delete student
        $student->delete();

        //redirect
        return redirect()->route('admin.students.index');
    }

    /**
     * import
     *
     * @return void
     */
    public function import()
    {
        return inertia('Admin/Students/Import');
    }

    /**
     * Download Excel template for student import
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
            'photo_url'
        ];

        $example = [
            '1234567890',
            'John Doe',
            '123456',
            'L',
            '1',
            'auto',
            'https://example.com/photo.jpg'
        ];

        $callback = function() use ($headers, $example) {
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
     * storeImport
     *
     * @param  mixed $request
     * @return void
     */
    public function storeImport(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,xls,xlsx'
        ]);

        // import data
        $import = new StudentsImport();
        Excel::import($import, $request->file('file'));

        // Check for duplicates and photo errors
        $duplicates = $import->getSkippedDuplicates();
        $photoErrors = $import->getPhotoErrors();
        
        $messages = [];
        
        if (count($duplicates) > 0) {
            $duplicateList = collect($duplicates)
                ->map(fn($d) => "Baris {$d['row']}: {$d['nisn']} - {$d['name']}")
                ->implode(', ');
            $messages[] = count($duplicates) . " siswa dilewati karena NISN sudah ada: " . $duplicateList;
        }
        
        if (count($photoErrors) > 0) {
            $photoErrorList = collect($photoErrors)
                ->map(fn($p) => "{$p['nisn']}: {$p['error']}")
                ->take(5)
                ->implode(', ');
            $messages[] = count($photoErrors) . " foto gagal didownload: " . $photoErrorList;
        }

        if (count($messages) > 0) {
            return redirect()->route('admin.students.index')
                ->with('warning', "Import selesai. " . implode('. ', $messages));
        }

        return redirect()->route('admin.students.index')
            ->with('success', 'Import siswa berhasil!');
    }

    /**
     * Import students from ZIP file containing Excel + photos
     */
    public function storeImportZip(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:zip|max:102400', // 100MB max
        ]);

        return $this->safeExecute(function () use ($request) {
            $zip = new \ZipArchive();
            $zipPath = $request->file('file')->getRealPath();

            if ($zip->open($zipPath) !== true) {
                return back()->with('error', 'Gagal membuka file ZIP.');
            }

            $tempDir = storage_path('app/temp/import_' . uniqid());
            mkdir($tempDir, 0755, true);

            // Extract ZIP
            $zip->extractTo($tempDir);
            $zip->close();

            // Find Excel/CSV file
            $dataFile = null;
            $possibleNames = ['data.xlsx', 'data.xls', 'data.csv', 'students.xlsx', 'students.xls', 'students.csv', 'siswa.xlsx', 'siswa.xls', 'siswa.csv'];
            
            foreach ($possibleNames as $name) {
                if (file_exists($tempDir . '/' . $name)) {
                    $dataFile = $tempDir . '/' . $name;
                    break;
                }
            }

            // Also search recursively one level
            if (!$dataFile) {
                $files = glob($tempDir . '/*/*.{xlsx,xls,csv}', GLOB_BRACE);
                if (!empty($files)) {
                    $dataFile = $files[0];
                }
            }

            if (!$dataFile) {
                $this->cleanupTempDir($tempDir);
                return back()->with('error', 'File Excel/CSV tidak ditemukan dalam ZIP. Harap beri nama: data.xlsx, data.csv, students.xlsx, atau siswa.xlsx');
            }

            // Find photos directory
            $photosDir = null;
            $possiblePhotoDirs = ['photos', 'photo', 'foto', 'images', 'img'];
            
            foreach ($possiblePhotoDirs as $dir) {
                if (is_dir($tempDir . '/' . $dir)) {
                    $photosDir = $tempDir . '/' . $dir;
                    break;
                }
            }

            // If no photos folder, check root for image files
            if (!$photosDir && glob($tempDir . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE)) {
                $photosDir = $tempDir;
            }

            // Import students from Excel
            $import = new StudentsImport();
            Excel::import($import, $dataFile);

            $studentsImported = 0;
            $photosMatched = 0;
            $photosFailed = [];

            // Get all newly imported students (those without photos)
            $duplicates = $import->getSkippedDuplicates();
            $duplicateNisns = collect($duplicates)->pluck('nisn')->toArray();

            // Match photos to students
            if ($photosDir) {
                $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
                $photoFiles = glob($photosDir . '/*.{' . implode(',', $allowedExt) . '}', GLOB_BRACE);

                foreach ($photoFiles as $photoPath) {
                    $filename = basename($photoPath);
                    $nisn = pathinfo($filename, PATHINFO_FILENAME);
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                    // Find student by NISN
                    $student = Student::where('nisn', $nisn)->first();
                    
                    if (!$student) {
                        $photosFailed[] = "{$nisn} - Siswa tidak ditemukan";
                        continue;
                    }

                    // Save photo
                    $content = file_get_contents($photoPath);
                    $newFilename = "students/{$nisn}." . $ext;
                    
                    \Storage::disk('public')->put($newFilename, $content);
                    
                    // Delete old photo if exists
                    if ($student->photo && \Storage::disk('public')->exists($student->photo)) {
                        \Storage::disk('public')->delete($student->photo);
                    }

                    $student->update(['photo' => $newFilename]);
                    $photosMatched++;
                }
            }

            // Cleanup temp directory
            $this->cleanupTempDir($tempDir);

            // Build result message
            $messages = [];
            
            if (count($duplicates) > 0) {
                $messages[] = count($duplicates) . " siswa dilewati (NISN sudah ada)";
            }
            
            if ($photosMatched > 0) {
                $messages[] = $photosMatched . " foto berhasil dipasangkan";
            }

            if (count($photosFailed) > 0) {
                $failedList = implode(', ', array_slice($photosFailed, 0, 5));
                if (count($photosFailed) > 5) $failedList .= '...';
                $messages[] = count($photosFailed) . " foto gagal: " . $failedList;
            }

            $resultMessage = "Import ZIP selesai.";
            if (!empty($messages)) {
                $resultMessage .= " " . implode('. ', $messages);
            }

            return redirect()->route('admin.students.index')
                ->with('success', $resultMessage);

        }, 'Gagal memproses file ZIP. Pastikan format sesuai.');
    }

    /**
     * Cleanup temporary directory
     */
    private function cleanupTempDir(string $dir): void
    {
        if (!is_dir($dir)) return;
        
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

    /**
     * Toggle block status of a student
     */
    public function toggleBlock(Student $student)
    {
        if ($student->is_blocked) {
            $student->unblock();
            $message = 'Siswa berhasil di-unblock.';
        } else {
            $student->block('Diblokir manual oleh admin');
            $message = 'Siswa berhasil diblokir.';
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Bulk password reset page
     */
    public function bulkPasswordReset()
    {
        $classrooms = Classroom::withCount('students')->get();
        
        return inertia('Admin/Students/BulkPasswordReset', [
            'classrooms' => $classrooms,
        ]);
    }

    /**
     * Execute bulk password reset
     */
    public function executeBulkPasswordReset(Request $request)
    {
        $request->validate([
            'classroom_id' => 'nullable|exists:classrooms,id',
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'exists:students,id',
            'password_type' => 'required|in:nisn,custom,random',
            'custom_password' => 'required_if:password_type,custom|nullable|min:6',
        ]);

        $query = Student::query();

        if ($request->student_ids && count($request->student_ids) > 0) {
            $query->whereIn('id', $request->student_ids);
        } elseif ($request->classroom_id) {
            $query->where('classroom_id', $request->classroom_id);
        } else {
            return back()->with('error', 'Pilih kelas atau siswa terlebih dahulu.');
        }

        return $this->executeInTransaction(function () use ($query, $request) {
            $students = $query->get();
            $count = 0;
            $results = [];

            foreach ($students as $student) {
                $newPassword = match ($request->password_type) {
                    'nisn' => $student->nisn,
                    'custom' => $request->custom_password,
                    'random' => $this->generateRandomPassword(),
                };

                $student->update(['password' => $newPassword]);
                $count++;

                if ($request->password_type === 'random') {
                    $results[] = [
                        'nisn' => $student->nisn,
                        'name' => $student->name,
                        'password' => $newPassword,
                    ];
                }
            }

            $response = ['success' => "{$count} password siswa berhasil direset."];
            
            if ($request->password_type === 'random') {
                $response['results'] = $results;
            }

            return back()->with($response);
        }, 'Gagal mereset password. Silakan coba lagi.');
    }

    /**
     * Generate random password
     */
    private function generateRandomPassword(int $length = 8): string
    {
        return substr(str_shuffle('abcdefghjkmnpqrstuvwxyz23456789'), 0, $length);
    }

    /**
     * Get students by classroom (API)
     */
    public function getByClassroom(Classroom $classroom)
    {
        $students = $classroom->students()->select('id', 'name', 'nisn')->get();
        return response()->json($students);
    }

    /**
     * Bulk photo upload page
     */
    public function bulkPhotoUpload()
    {
        return inertia('Admin/Students/BulkPhotoUpload');
    }

    /**
     * Process bulk photo upload (ZIP file with NISN-named photos)
     */
    public function processBulkPhotoUpload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:zip|max:51200', // 50MB max
        ]);

        return $this->safeExecute(function () use ($request) {
            $zip = new \ZipArchive();
            $zipPath = $request->file('file')->getRealPath();

            if ($zip->open($zipPath) !== true) {
                return back()->with('error', 'Gagal membuka file ZIP.');
            }

            $uploaded = 0;
            $failed = [];
            $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                $nisn = pathinfo($filename, PATHINFO_FILENAME);

                // Skip directories and non-image files
                if (!in_array($ext, $allowedExt)) continue;

                // Find student by NISN
                $student = Student::where('nisn', $nisn)->first();
                if (!$student) {
                    $failed[] = "{$nisn} - Siswa tidak ditemukan";
                    continue;
                }

                // Extract and save photo
                $content = $zip->getFromIndex($i);
                $newFilename = "students/{$nisn}.{$ext}";
                
                \Storage::disk('public')->put($newFilename, $content);
                
                // Delete old photo if exists
                if ($student->photo && \Storage::disk('public')->exists($student->photo)) {
                    \Storage::disk('public')->delete($student->photo);
                }

                $student->update(['photo' => $newFilename]);
                $uploaded++;
            }

            $zip->close();

            $message = "{$uploaded} foto berhasil diupload.";
            if (count($failed) > 0) {
                $message .= " " . count($failed) . " gagal: " . implode(', ', array_slice($failed, 0, 5));
                if (count($failed) > 5) $message .= "...";
            }

            return back()->with('success', $message);
        }, 'Gagal memproses file ZIP. Silakan coba lagi.');
    }
}