<?php

namespace App\Http\Controllers\Admin;

use App\Models\Lesson;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;

class LessonController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $lessons = Lesson::when(request()->q, function($lessons) {
            $lessons = $lessons->where('title', 'like', '%'. request()->q . '%');
        })->latest()->paginate(10);

        $lessons->appends(['q' => request()->q]);

        return inertia('Admin/Lessons/Index', [
            'lessons' => $lessons,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Admin/Lessons/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|unique:lessons',
        ]);

        Lesson::create([
            'title' => $request->title,
        ]);

        Cache::forget('lessons_all');

        return redirect()->route('admin.lessons.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $lesson = Lesson::findOrFail($id);

        return inertia('Admin/Lessons/Edit', [
            'lesson' => $lesson,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Lesson $lesson)
    {
        $request->validate([
            'title' => 'required|string|unique:lessons,title,'.$lesson->id,
        ]);

        $lesson->update([
            'title' => $request->title,
        ]);

        Cache::forget('lessons_all');

        return redirect()->route('admin.lessons.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $lesson = Lesson::findOrFail($id);
        $lesson->delete();

        Cache::forget('lessons_all');

        return redirect()->route('admin.lessons.index');
    }
}