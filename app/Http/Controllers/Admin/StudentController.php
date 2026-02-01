<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesTransactions;
use App\Http\Controllers\Traits\LogsActivity;
use App\Models\Classroom;
use App\Models\Room;
use App\Models\Student;
use App\Rules\StrongPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Controller for Student CRUD operations.
 * Import-related operations are handled by StudentImportController.
 */
class StudentController extends Controller
{
    use HandlesTransactions, LogsActivity;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $students = Student::when(request()->q, function ($students) {
            $students = $students->where('name', 'like', '%'.request()->q.'%');
        })->with(['classroom', 'room'])->latest()->paginate(10);

        $students->appends(['q' => request()->q]);

        return inertia('Admin/Students/Index', [
            'students' => $students,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Admin/Students/Create', [
            'classrooms' => Cache::remember('classrooms_all', 3600, fn () => Classroom::all()),
            'rooms' => Cache::remember('rooms_with_count', 300, fn () => Room::withCount('students')->get()),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'nisn' => ['required', Rule::unique('students')->whereNull('deleted_at')],
            'gender' => 'required|string|in:L,P',
            'password' => ['required', 'confirmed', StrongPassword::fromConfig()],
            'classroom_id' => 'required|exists:classrooms,id',
            'room_id' => 'nullable|exists:rooms,id',
            'auto_assign_room' => 'nullable|boolean',
        ]);

        // Auto assign random room if requested
        $roomId = $request->room_id;
        if ($request->auto_assign_room || ! $roomId) {
            $room = Room::getRandomAvailable();
            if (! $room) {
                return back()->withErrors(['room_id' => 'Semua ruangan sudah penuh!']);
            }
            $roomId = $room->id;
        }

        Student::create([
            'name' => $request->name,
            'nisn' => $request->nisn,
            'gender' => $request->gender,
            'password' => $request->password,
            'classroom_id' => $request->classroom_id,
            'room_id' => $roomId,
        ]);

        $this->logCreated('student', null, "Created student: {$request->name}");

        return redirect()->route('admin.students.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $student = Student::findOrFail($id);
        $classrooms = Classroom::all();

        return inertia('Admin/Students/Edit', [
            'student' => $student,
            'classrooms' => $classrooms,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Student $student)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'nisn' => ['required', Rule::unique('students')->ignore($student->id)->whereNull('deleted_at')],
            'gender' => 'required|string|in:L,P',
            'classroom_id' => 'required|exists:classrooms,id',
            'password' => ['nullable', 'confirmed', StrongPassword::fromConfig()],
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $updateData = [
            'name' => $request->name,
            'nisn' => $request->nisn,
            'gender' => $request->gender,
            'classroom_id' => $request->classroom_id,
        ];

        if ($request->password) {
            $updateData['password'] = $request->password;
        }

        // Handle photo upload
        if ($request->hasFile('photo')) {
            if ($student->photo && Storage::disk('public')->exists($student->photo)) {
                Storage::disk('public')->delete($student->photo);
            }

            $file = $request->file('photo');
            $ext = $file->getClientOriginalExtension();
            $filename = "students/{$request->nisn}.".$ext;

            $file->storeAs('students', "{$request->nisn}.{$ext}", 'public');
            $updateData['photo'] = $filename;
        }

        // Handle photo removal
        if ($request->has('remove_photo') && $request->remove_photo == '1') {
            if ($student->photo && Storage::disk('public')->exists($student->photo)) {
                Storage::disk('public')->delete($student->photo);
            }
            $updateData['photo'] = null;
        }

        $student->update($updateData);

        $this->logUpdated('student', $student, "Updated student: {$student->name}");

        return redirect()->route('admin.students.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $student = Student::findOrFail($id);

        $this->logDeleted('student', $student, "Deleted student: {$student->name} ({$student->nisn})");

        $student->delete();

        return redirect()->route('admin.students.index');
    }

    /**
     * Toggle block status of a student.
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
     * Bulk password reset page.
     */
    public function bulkPasswordReset()
    {
        $classrooms = Classroom::withCount('students')->get();

        return inertia('Admin/Students/BulkPasswordReset', [
            'classrooms' => $classrooms,
        ]);
    }

    /**
     * Execute bulk password reset.
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
     * Generate random password.
     */
    private function generateRandomPassword(int $length = 8): string
    {
        return substr(str_shuffle('abcdefghjkmnpqrstuvwxyz23456789'), 0, $length);
    }

    /**
     * Get students by classroom (API).
     */
    public function getByClassroom(Classroom $classroom)
    {
        $students = $classroom->students()->select('id', 'name', 'nisn')->get();

        return response()->json($students);
    }
}
