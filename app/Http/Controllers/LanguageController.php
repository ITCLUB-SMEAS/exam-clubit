<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class LanguageController extends Controller
{
    public function switch(Request $request)
    {
        $locale = $request->input('locale');
        
        if (!array_key_exists($locale, config('languages.available'))) {
            return back()->with('error', 'Invalid language');
        }

        Session::put('locale', $locale);

        // Update user preference if authenticated
        if ($request->user()) {
            $request->user()->update(['locale' => $locale]);
        }

        // Update student preference
        if (auth()->guard('student')->check()) {
            auth()->guard('student')->user()->update(['locale' => $locale]);
        }

        return back()->with('success', 'Language changed successfully');
    }
}
