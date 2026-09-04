<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

/**
 * Public sign-up. Self-registered accounts ALWAYS get the 'customer' role.
 * Sellers are created by admins; admins are seeded — neither can be created here.
 */
class RegisterController extends Controller
{
    public function show(Request $request)
    {
        $this->rememberContinueUrl($request);

        return view('auth.register');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone'    => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'phone'    => $data['phone'] ?? null,
            'password' => $data['password'],   // hashed via model cast
            'role'     => 'customer',          // forced — never trust input
        ]);

        try {
            event(new \Illuminate\Auth\Events\Registered($user));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Verification email failed to send: '.$e->getMessage());
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('home'))
            ->with('success', 'Welcome aboard, '.$user->name.'! Your account is ready.');
    }

    /**
     * Preserve a shopper's same-site destination without accepting external
     * redirect targets from the query string.
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
