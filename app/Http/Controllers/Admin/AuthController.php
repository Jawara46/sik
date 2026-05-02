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
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $adminEmail = (string) env('ADMIN_EMAIL', 'admin@sik.local');
        if (strcasecmp($credentials['email'], $adminEmail) !== 0) {
            return back()
                ->withErrors(['email' => 'Gunakan email admin yang valid untuk masuk ke panel admin.'])
                ->onlyInput('email');
        }

        if (!Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'Email atau password admin tidak valid.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
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
                'email_verified_at' => now(),
            ]);
        } catch (Throwable) {
            // Ignore provisioning failure and let the normal auth flow report login errors.
        }
    }
}
