<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TwoFactorChallengeController extends Controller
{
    public function __construct(protected TwoFactorService $twoFactor) {}

    public function show()
    {
        return Inertia::render('Admin/TwoFactor/Challenge');
    }

    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'recovery' => 'boolean',
        ]);

        $user = auth()->user();
        $verified = false;

        if ($request->recovery) {
            $verified = $this->twoFactor->verifyRecoveryCode($user, $request->code);
        } else {
            $verified = $this->twoFactor->verifyCode($user, $request->code);
        }

        if (!$verified) {
            return back()->with('error', 'Kode tidak valid.');
        }

        session(['2fa_verified' => $user->id]);

        return redirect()->intended(route('admin.dashboard'));
    }
}
