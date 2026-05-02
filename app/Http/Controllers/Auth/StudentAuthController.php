<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\View\View;

class StudentAuthController extends Controller
{
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'nisn' => 'required|string',
            'password' => 'required|string',
        ]);

        if (Auth::guard('student')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            $request->session()->flash('show_envelope', true);

            $student = Auth::guard('student')->user();
            ActivityLog::record(
                event: 'login',
                description: $student->name . ' berhasil login ke portal siswa.',
                subjectName: $student->name,
                subject: $student,
                ip: $request->ip(),
                ua: $request->userAgent()
            );

            return redirect()->intended(route('student.dashboard', absolute: false));
        }

        return back()->withErrors([
            'nisn' => 'NISN atau Password yang Anda masukkan salah. Pastikan password sesuai format Tanggal Lahir (DDMMYYYY).',
        ])->onlyInput('nisn');
    }

    public function logout(Request $request): RedirectResponse
    {
        $student = Auth::guard('student')->user();
        if ($student) {
            ActivityLog::record(
                event: 'logout',
                description: $student->name . ' keluar dari portal siswa.',
                subjectName: $student->name,
                subject: $student,
                ip: $request->ip(),
                ua: $request->userAgent()
            );
        }

        Auth::guard('student')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
