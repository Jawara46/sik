<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Throwable;

class AuthController extends Controller
{
    public function showLoginForm(): View|RedirectResponse
    {
        $this->ensureDefaultAdminExists();

        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $this->ensureDefaultAdminExists();

        $credentials = $request->validate([
            'email' => ['required_if:login_mode,password', 'nullable', 'email'],
            'password' => ['required_if:login_mode,password'],
            'pin' => ['required_if:login_mode,pin', 'nullable', 'string', 'size:6'],
            'login_mode' => ['required', 'in:password,pin'],
        ]);

        $adminEmail = (string) env('ADMIN_EMAIL', 'admin@sik.local');
        if ($credentials['login_mode'] === 'password' && strcasecmp((string)($credentials['email'] ?? ''), $adminEmail) !== 0) {
            return back()
                ->withErrors(['email' => 'Gunakan email admin yang valid untuk masuk ke panel admin.'])
                ->onlyInput('email');
        }

        $user = User::where('email', $adminEmail)->first();
        if (!$user) {
            return back()->withErrors(['email' => 'User admin tidak ditemukan.']);
        }

        $authenticated = false;
        if ($credentials['login_mode'] === 'pin') {
            if ($user->pin && $credentials['pin'] === $user->pin) {
                Auth::guard('admin')->login($user, $request->boolean('remember'));
                $authenticated = true;
            } else {
                return back()
                    ->withErrors(['pin' => 'PIN admin tidak valid atau belum diatur.'])
                    ->onlyInput('email');
            }
        } else {
            if (Auth::guard('admin')->attempt(['email' => $adminEmail, 'password' => $credentials['password']], $request->boolean('remember'))) {
                $authenticated = true;
            } else {
                return back()
                    ->withErrors(['email' => 'Email atau password admin tidak valid.'])
                    ->onlyInput('email');
            }
        }

        if ($authenticated) {
            $request->session()->regenerate();
            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->withErrors(['email' => 'Login gagal.']);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    private function ensureDefaultAdminExists(): void
    {
        try {
            $adminEmail = (string) env('ADMIN_EMAIL', 'admin@sik.local');

            if (User::query()->where('email', $adminEmail)->exists()) {
                return;
            }

            User::query()->create([
                'name' => env('ADMIN_NAME', 'Super Admin'),
                'email' => $adminEmail,
                'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
                'pin' => '123456',
                'email_verified_at' => now(),
            ]);
        } catch (Throwable) {
            // Ignore provisioning failure and let the normal auth flow report login errors.
        }
    }
}
