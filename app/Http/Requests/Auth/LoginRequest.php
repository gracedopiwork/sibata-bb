<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Services\UserLicense;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'license_key' => ['required', 'string', 'max:40'],
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi tidak sesuai.',
            ]);
        }

        /** @var User $user */
        $user = Auth::user();

        $providedLicense = UserLicense::normalize($this->input('license_key'));

        if (! $user->hasValidLicense() || ! hash_equals((string) $user->license_key, $providedLicense)) {
            Auth::logout();
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'Email, kata sandi, atau kode lisensi tidak sesuai.',
            ]);
        }

        if (! $user->is_active) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Akun Anda tidak aktif. Hubungi administrator.',
            ]);
        }

        if (! $user->canAccessDashboard()) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Dashboard hanya untuk administrator. Petugas PB3R memakai bot Telegram.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => 'Terlalu banyak percobaan. Coba lagi dalam '.$seconds.' detik.',
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
