<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginHistory;
use App\Rules\StrongPassword;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function __construct(protected TwoFactorService $twoFactor) {}

    public function index()
    {
        $user = auth()->user();
        
        return inertia('Admin/Profile/Index', [
            'user' => $user,
            'twoFactorEnabled' => $this->twoFactor->isEnabled($user),
            'recoveryCodes' => $user->two_factor_recovery_codes 
                ? json_decode(decrypt($user->two_factor_recovery_codes), true) 
                : null,
            'loginHistory' => LoginHistory::where('user_type', 'admin')
                ->where('user_id', $user->id)
                ->latest()
                ->limit(10)
                ->get(),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . auth()->id(),
        ]);

        auth()->user()->update($request->only('name', 'email'));

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password' => ['required', 'confirmed', new StrongPassword()],
        ]);

        auth()->user()->update(['password' => Hash::make($request->password)]);

        return back()->with('success', 'Password berhasil diperbarui.');
    }

    public function updatePhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|max:2048',
        ]);

        $user = auth()->user();

        // Delete old photo
        if ($user->photo && strlen($user->photo) > 1 && Storage::disk('public')->exists($user->photo)) {
            Storage::disk('public')->delete($user->photo);
        }

        $path = $request->file('photo')->store('avatars', 'public');
        $user->photo = $path;
        $user->save();

        return back()->with('success', 'Foto profil berhasil diperbarui.');
    }

    // 2FA Methods
    public function setup2FA()
    {
        $secret = $this->twoFactor->generateSecretKey();
        $user = auth()->user();
        
        session(['2fa_secret' => $secret]);

        return response()->json([
            'secret' => $secret,
            'qrCodeUrl' => $this->twoFactor->getQRCodeUrl($user, $secret),
        ]);
    }

    public function enable2FA(Request $request)
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
        return back()->with('success', '2FA berhasil diaktifkan.');
    }

    public function disable2FA(Request $request)
    {
        $request->validate(['password' => 'required|current_password']);
        $this->twoFactor->disable(auth()->user());
        return back()->with('success', '2FA berhasil dinonaktifkan.');
    }

    public function regenerateCodes(Request $request)
    {
        $request->validate(['password' => 'required|current_password']);
        
        $codes = $this->twoFactor->generateRecoveryCodes();
        auth()->user()->update([
            'two_factor_recovery_codes' => encrypt(json_encode($codes)),
        ]);

        return back()->with('success', 'Recovery codes berhasil di-generate ulang.');
    }
}
