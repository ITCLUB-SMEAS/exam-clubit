<?php

namespace App\Services;

use App\Models\User;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TwoFactorService
{
    protected Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    public function generateSecretKey(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    public function getQRCodeUrl(User $user, string $secret): string
    {
        return $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );
    }

    public function verify(string $secret, string $code): bool
    {
        return $this->google2fa->verifyKey($secret, $code);
    }

    public function generateRecoveryCodes(): array
    {
        return Collection::times(8, fn() => Str::random(10))->all();
    }

    public function enable(User $user, string $secret, string $code): bool
    {
        if (!$this->verify($secret, $code)) {
            return false;
        }

        $user->update([
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => encrypt(json_encode($this->generateRecoveryCodes())),
            'two_factor_confirmed_at' => now(),
        ]);

        return true;
    }

    public function disable(User $user): void
    {
        $user->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);
    }

    public function isEnabled(User $user): bool
    {
        return !is_null($user->two_factor_confirmed_at);
    }

    public function verifyCode(User $user, string $code): bool
    {
        if (!$user->two_factor_secret) return false;

        $secret = decrypt($user->two_factor_secret);
        return $this->verify($secret, $code);
    }

    public function verifyRecoveryCode(User $user, string $code): bool
    {
        if (!$user->two_factor_recovery_codes) return false;

        $codes = json_decode(decrypt($user->two_factor_recovery_codes), true);
        
        if (!in_array($code, $codes)) return false;

        // Remove used code
        $codes = array_values(array_diff($codes, [$code]));
        $user->update([
            'two_factor_recovery_codes' => encrypt(json_encode($codes)),
        ]);

        return true;
    }
}
