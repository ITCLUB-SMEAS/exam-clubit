<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuestionCategory;
use App\Models\Lesson;
use Illuminate\Http\Request;

class QuestionCategoryController extends Controller
{
    public function index()
    {
        $categories = QuestionCategory::with('lesson')
            ->withCount('questionBanks')
            ->latest()
            ->paginate(10);

        return inertia('Admin/QuestionCategories/Index', compact('categories'));
    }

    public function create()
    {
        $lessons = Lesson::all();
        return inertia('Admin/QuestionCategories/Create', compact('lessons'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'lesson_id' => 'nullable|exists:lessons,id',
        ]);

        QuestionCategory::create($request->all());

        return redirect()->route('admin.question-categories.index')
            ->with('success', 'Kategori berhasil ditambahkan');
    }

    public function edit(QuestionCategory $questionCategory)
    {
        $lessons = Lesson::all();
        return inertia('Admin/QuestionCategories/Edit', [
            'category' => $questionCategory,
            'lessons' => $lessons,
        ]);
    }

    public function update(Request $request, QuestionCategory $questionCategory)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'lesson_id' => 'nullable|exists:lessons,id',
        ]);

        $questionCategory->update($request->all());

        return redirect()->route('admin.question-categories.index')
            ->with('success', 'Kategori berhasil diupdate');
    }

    public function destroy(QuestionCategory $questionCategory)
    {
        $questionCategory->delete();

        return redirect()->route('admin.question-categories.index')
            ->with('success', 'Kategori berhasil dihapus');
    }
}
