<?php

namespace App\Http\Controllers\Admin;

use App\Models\Classroom;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;

class ClassroomController extends Controller
{
    public function index()
    {
        $classrooms = Classroom::when(request()->q, function($classrooms) {
            $classrooms = $classrooms->where('title', 'like', '%'. request()->q . '%');
        })->latest()->paginate(10);

        $classrooms->appends(['q' => request()->q]);

        return inertia('Admin/Classrooms/Index', [
            'classrooms' => $classrooms,
        ]);
    }

    public function create()
    {
        return inertia('Admin/Classrooms/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|unique:classrooms'
        ]);

        Classroom::create([
            'title' => $request->title,
        ]);

        Cache::forget('classrooms_all');

        return redirect()->route('admin.classrooms.index');
    }

    public function edit($id)
    {
        $classroom = Classroom::findOrFail($id);

        return inertia('Admin/Classrooms/Edit', [
            'classroom' => $classroom,
        ]);
    }

    public function update(Request $request, Classroom $classroom)
    {
        $request->validate([
            'title' => 'required|string|unique:classrooms,title,'.$classroom->id,
        ]);

        $classroom->update([
            'title' => $request->title,
        ]);

        Cache::forget('classrooms_all');

        return redirect()->route('admin.classrooms.index');
    }

    public function destroy($id)
    {
        $classroom = Classroom::findOrFail($id);
        $classroom->delete();

        Cache::forget('classrooms_all');

        return redirect()->route('admin.classrooms.index');
    }
}
