<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminProfileService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function __construct(
        private readonly AdminProfileService $adminProfileService,
    ) {
    }

    public function index(): View
    {
        return view('admin.profile.index', [
            'admin' => Auth::guard('admin')->user(),
            'activeTab' => request()->query('tab', 'profile'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $admin = Auth::guard('admin')->user();
        abort_unless($admin !== null, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($admin->id),
            ],
            'nomor_wa' => ['required', 'string', 'max:20'],
            'alamat' => ['nullable', 'string', 'max:1000'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'verifikasi_password' => ['required', 'string'],
        ]);

        if (!Hash::check($validated['verifikasi_password'], (string) $admin->password)) {
            return back()->withErrors([
                'verifikasi_password' => 'Password verifikasi tidak sesuai.',
            ])->withInput()->withFragment('profile-tab-panel');
        }

        $this->adminProfileService->updateProfile($admin, $validated, [
            'avatar' => $request->file('avatar'),
        ]);

        return redirect()
            ->route('admin.profile.index', ['tab' => 'profile'])
            ->with('status', 'Profil admin berhasil diperbarui.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $admin = Auth::guard('admin')->user();
        abort_unless($admin !== null, 403);

        $validated = $request->validate([
            'password_lama' => ['required', 'string'],
            'password_baru' => ['required', 'string', 'min:8', 'different:password_lama'],
            'konfirmasi_password' => ['required', 'same:password_baru'],
        ]);

        if (!Hash::check($validated['password_lama'], (string) $admin->password)) {
            return back()->withErrors([
                'password_lama' => 'Password lama tidak sesuai.',
            ])->withFragment('security-card');
        }

        $this->adminProfileService->updatePassword($admin, $validated['password_baru']);

        $request->session()->regenerate();

        return redirect()
            ->route('admin.profile.index', ['tab' => 'security'])
            ->withFragment('security-card')
            ->with('status', 'Password admin berhasil diperbarui.');
    }

    public function destroyAvatar(): RedirectResponse
    {
        $admin = Auth::guard('admin')->user();
        abort_unless($admin !== null, 403);

        $this->adminProfileService->deleteAvatar($admin);

        return redirect()
            ->route('admin.profile.index', ['tab' => 'profile'])
            ->with('status', 'Foto profil admin berhasil dihapus.');
    }
}
