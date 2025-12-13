<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuestionCategory;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class QuestionCategoryController extends Controller
{
    public function index()
    {
        // Redirect to question-bank with categories tab
        return redirect()->route('admin.question-bank.index');
    }

    public function create()
    {
        return redirect()->route('admin.question-bank.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        QuestionCategory::create($request->only(['name', 'description']));
        Cache::forget('question_categories_with_count');

        return redirect()->route('admin.question-bank.index')
            ->with('success', 'Kategori berhasil ditambahkan');
    }

    public function edit(QuestionCategory $questionCategory)
    {
        return redirect()->route('admin.question-bank.index');
    }

    public function update(Request $request, QuestionCategory $questionCategory)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $questionCategory->update($request->only(['name', 'description']));
        Cache::forget('question_categories_with_count');

        return redirect()->route('admin.question-bank.index')
            ->with('success', 'Kategori berhasil diupdate');
    }

    public function destroy(QuestionCategory $questionCategory)
    {
        if ($questionCategory->questions()->count() > 0) {
            return back()->with('error', 'Kategori tidak bisa dihapus karena masih memiliki soal');
        }

        $questionCategory->delete();
        Cache::forget('question_categories_with_count');

        return redirect()->route('admin.question-bank.index')
            ->with('success', 'Kategori berhasil dihapus');
    }
}
