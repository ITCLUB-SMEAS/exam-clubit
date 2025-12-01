<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $students = Student::with('classroom')
            ->when($request->classroom_id, fn($q) => $q->where('classroom_id', $request->classroom_id))
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->paginate($request->per_page ?? 15);

        return response()->json($students);
    }

    public function show(Student $student)
    {
        return response()->json($student->load('classroom'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nisn' => 'required|unique:students,nisn',
            'name' => 'required|string',
            'classroom_id' => 'required|exists:classrooms,id',
            'password' => 'required|min:6',
            'gender' => 'required|in:L,P',
        ]);

        $validated['password'] = bcrypt($validated['password']);
        $student = Student::create($validated);

        return response()->json($student, 201);
    }

    public function update(Request $request, Student $student)
    {
        $validated = $request->validate([
            'nisn' => 'required|unique:students,nisn,' . $student->id,
            'name' => 'required|string',
            'classroom_id' => 'required|exists:classrooms,id',
            'gender' => 'required|in:L,P',
        ]);

        if ($request->filled('password')) {
            $validated['password'] = bcrypt($request->password);
        }

        $student->update($validated);

        return response()->json($student);
    }

    public function destroy(Student $student)
    {
        $student->delete();
        return response()->json(['message' => 'Student deleted']);
    }
}
