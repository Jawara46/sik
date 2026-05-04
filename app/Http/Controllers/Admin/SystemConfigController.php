<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AppUpdateService;
use App\Services\DatabaseBackupService;
use App\Services\SchoolProfileService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SystemConfigController extends Controller
{
    public function __construct(
        private readonly SchoolProfileService $schoolProfileService,
        private readonly DatabaseBackupService $databaseBackupService,
        private readonly AppUpdateService $appUpdateService,
    ) {
    }

    public function branding(): View
    {
        return view('admin.settings.branding', [
            'school' => $this->schoolProfileService->getCurrentSchool(),
        ]);
    }

    public function backup(): View
    {
        return view('admin.settings.backup', [
            'backups' => $this->databaseBackupService->list(),
            'databaseName' => (string) config('database.connections.mysql.database'),
        ]);
    }

    public function updateBranding(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'logo' => ['nullable', 'image', 'max:2048'],
            'kop_surat' => ['nullable', 'image', 'max:4096'],
            'ttd_kepsek' => ['nullable', 'image', 'mimes:png', 'max:2048'],
            'stempel_sekolah' => ['nullable', 'image', 'mimes:png', 'max:2048'],
            'bg_countdown' => ['nullable', 'image', 'max:4096'],
            'use_digital_stamp' => ['nullable', 'boolean'],
        ]);

        $validated['use_digital_stamp'] = $request->boolean('use_digital_stamp');

        $this->schoolProfileService->updateProfile($validated, [
            'logo' => $request->file('logo'),
            'kop_surat' => $request->file('kop_surat'),
            'ttd_kepsek' => $request->file('ttd_kepsek'),
            'stempel_sekolah' => $request->file('stempel_sekolah'),
            'bg_countdown' => $request->file('bg_countdown'),
        ]);

        return redirect()
            ->route('admin.settings.branding.index')
            ->with('status', 'Branding sistem berhasil diperbarui.');
    }

    public function createBackup(): RedirectResponse
    {
        $backup = $this->databaseBackupService->create();

        \App\Models\Notification::create([
            'title' => 'Backup Database',
            'message' => "Backup database <strong>{$backup['filename']}</strong> berhasil dibuat secara manual.",
            'type' => 'primary',
            'icon' => 'ri-database-2-line',
        ]);

        return redirect()
            ->route('admin.settings.backup.index')
            ->with('status', sprintf(
                'Backup database berhasil dibuat: %s (%s byte).',
                $backup['filename'],
                number_format((int) $backup['size']),
            ));
    }

    public function downloadBackup(string $filename): BinaryFileResponse
    {
        abort_unless($this->databaseBackupService->exists($filename), 404);

        return response()->download(
            $this->databaseBackupService->path($filename),
            basename($filename),
        );
    }

    public function deleteBackup(string $filename): RedirectResponse
    {
        abort_unless($this->databaseBackupService->exists($filename), 404);

        unlink($this->databaseBackupService->path($filename));

        return redirect()
            ->route('admin.settings.backup.index')
            ->with('status', 'File backup berhasil dihapus.');
    }

    /**
     * About / Tentang Aplikasi page.
     */
    public function about(): View
    {
        return view('admin.settings.about', [
            'version' => $this->appUpdateService->getCurrentVersion(),
            'env' => $this->appUpdateService->getEnvironmentInfo(),
        ]);
    }

    /**
     * AJAX: Check for available updates.
     */
    public function checkUpdate(): JsonResponse
    {
        $update = $this->appUpdateService->checkForUpdate();

        if ($update === null) {
            return response()->json(['error' => true, 'message' => 'Gagal memeriksa update.']);
        }

        return response()->json([
            'has_update' => $update['has_update'],
            'tag' => $update['tag'],
            'notes' => $update['notes'],
            'date' => $update['date'],
            'current' => $this->appUpdateService->getCurrentVersion(),
        ]);
    }

    /**
     * AJAX: Perform the update.
     */
    public function performUpdate(): JsonResponse
    {
        $result = $this->appUpdateService->performUpdate();

        return response()->json($result);
    }

    /**
     * AJAX: Fix storage:link for shared hosting.
     */
    public function fixStorageLink(): JsonResponse
    {
        try {
            if (is_link(public_path('storage'))) {
                return response()->json(['success' => true, 'message' => 'Tautan penyimpanan sudah ada.']);
            }

            if (file_exists(public_path('storage'))) {
                // If it's a directory but not a link, we might need to be careful
                return response()->json(['success' => false, 'message' => 'Folder "public/storage" sudah ada sebagai direktori biasa. Hapus folder tersebut terlebih dahulu.']);
            }

            \Illuminate\Support\Facades\Artisan::call('storage:link');
            
            return response()->json(['success' => true, 'message' => 'Tautan penyimpanan berhasil dibuat.']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Gagal membuat tautan: ' . $e->getMessage()]);
        }
    }
}

