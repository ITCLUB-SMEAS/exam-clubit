<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function index()
    {
        $student = auth()->guard('student')->user()->load('classroom');
        
        return inertia('Student/Profile/Index', [
            'student' => $student,
        ]);
    }

    public function update(Request $request)
    {
        $student = auth()->guard('student')->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'gender' => 'required|in:L,P',
        ]);

        $student->update([
            'name' => $request->name,
            'gender' => $request->gender,
        ]);

        return back()->with('success', 'Profil berhasil diupdate');
    }

    public function updatePassword(Request $request)
    {
        $student = auth()->guard('student')->user();

        $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'confirmed', Password::min(6)],
        ]);

        if (!Hash::check($request->current_password, $student->password)) {
            return back()->withErrors(['current_password' => 'Password lama tidak sesuai']);
        }

        $student->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Password berhasil diubah');
    }
}
