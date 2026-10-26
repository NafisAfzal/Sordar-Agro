<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class PasswordController extends Controller
{
    // ---- Change password (while logged in) ----------------------------
    public function showChange()
    {
        return view('auth.change-password');
    }

    public function updateChange(Request $request)
    {
        $user = $request->user();

        // A seller using a temporary password should not need to know it,
        // so we only require the current password for normal changes.
        $rules = [
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ];
        if (! $user->must_change_password) {
            $rules['current_password'] = ['required', 'current_password'];
        }
        $request->validate($rules);

        $user->forceFill([
            'password' => $request->password,
            'must_change_password' => false,
            'remember_token' => Str::random(60),
        ])->save();

        // Invalidate every other session that authenticated with the old
        // password hash, while keeping the current session signed in.
        Auth::logoutOtherDevices($request->password);

        $request->session()->regenerate();

        return redirect()->route($user->homeRoute())
            ->with('success', 'Your password has been updated.');
    }

    // ---- Forgot password (request reset link) -------------------------
    public function showForgot()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink($request->only('email'));

        // Never reveal whether an email address is registered: a throttled
        // request is the only failure we surface, everything else is generic.
        if ($status === Password::RESET_THROTTLED) {
            return back()->withErrors(['email' => __($status)]);
        }

        return back()->with('success', 'If an account exists for that address, a password reset link has been sent.');
    }

    // ---- Reset password (from emailed link) ---------------------------
    public function showReset(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', __($status))
            : back()->withErrors(['email' => __($status)]);
    }
}
