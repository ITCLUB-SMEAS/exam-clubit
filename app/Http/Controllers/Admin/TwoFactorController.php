<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TwoFactorController extends Controller
{
    public function __construct(protected TwoFactorService $twoFactor) {}

    public function show()
    {
        $user = auth()->user();
        
        return Inertia::render('Admin/TwoFactor/Index', [
            'enabled' => $this->twoFactor->isEnabled($user),
            'recoveryCodes' => $user->two_factor_recovery_codes 
                ? json_decode(decrypt($user->two_factor_recovery_codes), true) 
                : null,
        ]);
    }

    public function setup()
    {
        $secret = $this->twoFactor->generateSecretKey();
        $user = auth()->user();
        
        session(['2fa_secret' => $secret]);

        return Inertia::render('Admin/TwoFactor/Setup', [
            'secret' => $secret,
            'qrCodeUrl' => $this->twoFactor->getQRCodeUrl($user, $secret),
        ]);
    }

    public function enable(Request $request)
    {
        $request->validate(['code' => 'required|string|size:6']);

        $secret = session('2fa_secret');
        if (!$secret) {
            return back()->with('error', 'Sesi setup telah berakhir.');
        }

        if (!$this->twoFactor->enable(auth()->user(), $secret, $request->code)) {
            return back()->with('error', 'Kode tidak valid.');
        }

        session()->forget('2fa_secret');

        return redirect()->route('admin.two-factor.show')
            ->with('success', '2FA berhasil diaktifkan.');
    }

    public function disable(Request $request)
    {
        $request->validate(['password' => 'required|current_password']);

        $this->twoFactor->disable(auth()->user());

        return redirect()->route('admin.two-factor.show')
            ->with('success', '2FA berhasil dinonaktifkan.');
    }

    public function regenerateCodes(Request $request)
    {
        $request->validate(['password' => 'required|current_password']);

        $user = auth()->user();
        $codes = $this->twoFactor->generateRecoveryCodes();
        
        $user->update([
            'two_factor_recovery_codes' => encrypt(json_encode($codes)),
        ]);

        return back()->with('success', 'Recovery codes berhasil di-generate ulang.');
    }
}
