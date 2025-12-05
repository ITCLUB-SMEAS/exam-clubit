<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionVersion;
use Illuminate\Http\Request;

class QuestionVersionController extends Controller
{
    public function index(Question $question)
    {
        return response()->json([
            'versions' => $question->versions()->with('user:id,name')->get(),
        ]);
    }

    public function restore(Question $question, int $version)
    {
        if (!$question->restoreVersion($version)) {
            return back()->with('error', 'Versi tidak ditemukan.');
        }

        // Create new version after restore
        $question->createVersion(auth()->id(), "Restored from version {$version}");

        return back()->with('success', "Soal berhasil dikembalikan ke versi {$version}.");
    }
}
