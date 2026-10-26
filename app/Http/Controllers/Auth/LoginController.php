<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function show(Request $request)
    {
        $this->rememberContinueUrl($request);

        return view('auth.login');
    }

    public function store(Request $request)
    {
        $email = Str::lower((string) $request->input('email'));

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);
        $credentials['email'] = $email;

        // Per-email+IP throttle on failed attempts, cleared on success.
        $throttleKey = 'login:'.$email.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => 'Too many login attempts. Please try again in '.$seconds.' seconds.',
            ]);
        }

        // Block suspended accounts even with correct credentials.
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        if (! $request->user()->is_active) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => 'This account has been suspended. Please contact support.',
            ]);
        }

        $request->session()->regenerate();

        // Sellers with a temporary password go straight to change-password.
        if ($request->user()->must_change_password) {
            return redirect()->route('password.change');
        }

        return redirect()->intended(route($request->user()->homeRoute()))
            ->with('success', 'Signed in successfully.');
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'You have been logged out.');
    }

    /**
     * Keep shoppers on the same on-site product page after they sign in.
     * The host check prevents an arbitrary query parameter from becoming an
     * external redirect target.
     */
    private function rememberContinueUrl(Request $request): void
    {
        $continue = $request->query('continue');

        if (! is_string($continue) || ! filter_var($continue, FILTER_VALIDATE_URL)) {
            return;
        }

        if (parse_url($continue, PHP_URL_HOST) !== $request->getHost()
            || parse_url($continue, PHP_URL_SCHEME) !== $request->getScheme()) {
            return;
        }

        $request->session()->put('url.intended', $continue);
    }
}
