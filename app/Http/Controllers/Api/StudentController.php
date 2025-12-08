<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreStudentRequest;
use App\Http\Requests\Api\UpdateStudentRequest;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
            'classroom_id' => 'nullable|integer|exists:classrooms,id',
            'search' => 'nullable|string|max:255',
        ]);

        $perPage = min($request->per_page ?? 15, 100);
        
        $students = Student::with('classroom')
            ->when($request->classroom_id, fn($q) => $q->where('classroom_id', $request->classroom_id))
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->paginate($perPage);

        return response()->json($students);
    }

    public function show(Student $student)
    {
        return response()->json($student->load('classroom'));
    }

    public function store(StoreStudentRequest $request)
    {
        $validated = $request->validated();
        $validated['password'] = Hash::make($validated['password']);
        
        $student = Student::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Student created successfully',
            'data' => $student
        ], 201);
    }

    public function update(UpdateStudentRequest $request, Student $student)
    {
        $validated = $request->validated();

        if ($request->filled('password')) {
            $validated['password'] = Hash::make($request->password);
        }

        $student->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Student updated successfully',
            'data' => $student
        ]);
    }

    public function destroy(Student $student)
    {
        $student->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Student deleted successfully'
        ]);
    }
}
