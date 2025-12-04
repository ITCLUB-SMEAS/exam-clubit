<?php

namespace App\Http\Controllers\Admin;

use App\Models\Student;
use App\Models\Classroom;
use App\Models\Room;
use Illuminate\Http\Request;
use App\Imports\StudentsImport;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;

class StudentController extends Controller
{
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
            'classrooms' => Classroom::all(),
            'rooms' => Room::withCount('students')->get(),
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
            'nisn'              => 'required|unique:students',
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
            'nisn'          => 'required|unique:students,nisn,'.$student->id,
            'gender'        => 'required|string',
            'classroom_id'  => 'required',
            'password'      => 'confirmed'
        ]);

        //check passwordy
        if($request->password == "") {

            //update student without password
            $student->update([
                'name'          => $request->name,
                'nisn'          => $request->nisn,
                'gender'        => $request->gender,
                'classroom_id'  => $request->classroom_id
            ]);

        } else {

            //update student with password
            $student->update([
                'name'          => $request->name,
                'nisn'          => $request->nisn,
                'gender'        => $request->gender,
                'password'      => $request->password,
                'classroom_id'  => $request->classroom_id
            ]);

        }

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
        Excel::import(new StudentsImport(), $request->file('file'));

        //redirect
        return redirect()->route('admin.students.index');
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
}