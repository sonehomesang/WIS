<?php

namespace App\Livewire\Forms;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Form;

class LoginForm extends Form
{
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    #[Validate('boolean')]
    public bool $remember = false;

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only(['email', 'password']), $this->remember)) {
            RateLimiter::hit($this->throttleKey());
            RateLimiter::hit($this->accountKey());

            throw ValidationException::withMessages([
                'form.email' => trans('auth.failed'),
            ]);
        }

        // locked / not-yet-approved accounts may not enter even with valid creds
        if (Auth::user()->status !== 'active') {
            $status = Auth::user()->status;
            Auth::logout();
            RateLimiter::hit($this->throttleKey());
            RateLimiter::hit($this->accountKey());

            throw ValidationException::withMessages([
                'form.email' => $status === 'pending'
                    ? 'ບັນຊີ ລໍ ການອະນຸມັດ — ຕິດຕໍ່ admin.'
                    : 'ບັນຊີ ຖືກລັອກ — ຕິດຕໍ່ admin.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        RateLimiter::clear($this->accountKey());
    }

    /**
     * Ensure the authentication request is not rate limited.
     * per-(email+IP) = 5 · per-account across ALL IPs = 10 (ກັນ distributed password-spray).
     */
    protected function ensureIsNotRateLimited(): void
    {
        foreach ([$this->throttleKey() => 5, $this->accountKey() => 10] as $key => $max) {
            if (! RateLimiter::tooManyAttempts($key, $max)) {
                continue;
            }

            event(new Lockout(request()));
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'form.email' => trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }
    }

    /** per-(email+IP) throttle key. */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }

    /** per-account throttle key (email only, across all IPs). */
    protected function accountKey(): string
    {
        return 'acct:'.Str::transliterate(Str::lower($this->email));
    }
}
