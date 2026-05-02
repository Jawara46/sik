<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use ZipArchive;
use Throwable;

class AppUpdateService
{
    /**
     * Detect how the application was installed.
     *
     * @return string 'git' | 'zip'
     */
    public function getInstallMethod(): string
    {
        return is_dir(base_path('.git')) ? 'git' : 'zip';
    }

    /**
     * Get the current installed version from config.
     */
    public function getCurrentVersion(): string
    {
        return (string) config('sik.version', '1.0.0');
    }

    /**
     * Check GitHub for the latest release.
     *
     * @return array{tag: string, url: string, notes: string, date: string, has_update: bool}|null
     */
    public function checkForUpdate(): ?array
    {
        try {
            $repo = config('sik.github_repo', 'Jawara46/sik');
            $response = Http::timeout(10)
                ->withHeaders(['Accept' => 'application/vnd.github.v3+json'])
                ->get("https://api.github.com/repos/{$repo}/releases/latest");

            if (! $response->successful()) {
                // Fallback: check tags if no releases
                return $this->checkTagsFallback($repo);
            }

            $data = $response->json();
            $latestTag = ltrim((string) ($data['tag_name'] ?? ''), 'v');
            $currentVersion = $this->getCurrentVersion();

            return [
                'tag' => $latestTag,
                'url' => (string) ($data['zipball_url'] ?? ''),
                'notes' => (string) ($data['body'] ?? 'Tidak ada catatan rilis.'),
                'date' => (string) ($data['published_at'] ?? now()->toIso8601String()),
                'has_update' => version_compare($latestTag, $currentVersion, '>'),
            ];
        } catch (Throwable $e) {
            Log::warning('AppUpdateService::checkForUpdate failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Fallback: check latest tag from GitHub if there are no releases.
     */
    private function checkTagsFallback(string $repo): ?array
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders(['Accept' => 'application/vnd.github.v3+json'])
                ->get("https://api.github.com/repos/{$repo}/tags", ['per_page' => 1]);

            if (! $response->successful() || empty($response->json())) {
                return [
                    'tag' => $this->getCurrentVersion(),
                    'url' => '',
                    'notes' => 'Tidak ada rilis ditemukan di repository.',
                    'date' => now()->toIso8601String(),
                    'has_update' => false,
                ];
            }

            $tag = $response->json()[0];
            $latestTag = ltrim((string) ($tag['name'] ?? ''), 'v');

            return [
                'tag' => $latestTag,
                'url' => "https://api.github.com/repos/{$repo}/zipball/{$tag['name']}",
                'notes' => 'Rilis dari tag terbaru.',
                'date' => now()->toIso8601String(),
                'has_update' => version_compare($latestTag, $this->getCurrentVersion(), '>'),
            ];
        } catch (Throwable $e) {
            Log::warning('AppUpdateService::checkTagsFallback failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Perform the update.
     *
     * @return array{success: bool, method: string, message: string, log: array<string>}
     */
    public function performUpdate(): array
    {
        $method = $this->getInstallMethod();
        $log = [];

        try {
            if ($method === 'git') {
                return $this->performGitUpdate($log);
            }

            return $this->performZipUpdate($log);
        } catch (Throwable $e) {
            $log[] = '❌ Error: ' . $e->getMessage();
            Log::error('AppUpdateService::performUpdate failed', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'method' => $method,
                'message' => 'Gagal melakukan update: ' . $e->getMessage(),
                'log' => $log,
            ];
        }
    }

    /**
     * Update via git pull.
     *
     * @param array<string> &$log
     * @return array{success: bool, method: string, message: string, log: array<string>}
     */
    private function performGitUpdate(array &$log): array
    {
        $log[] = '🔍 Metode instalasi: Git';
        $log[] = '📥 Menjalankan git pull origin main...';

        $output = [];
        $returnCode = 0;

        exec('cd ' . escapeshellarg(base_path()) . ' && git pull origin main 2>&1', $output, $returnCode);
        $log = array_merge($log, $output);

        if ($returnCode !== 0) {
            $log[] = '❌ git pull gagal dengan kode: ' . $returnCode;
            return [
                'success' => false,
                'method' => 'git',
                'message' => 'Git pull gagal. Periksa log untuk detail.',
                'log' => $log,
            ];
        }

        $log[] = '✅ Git pull berhasil.';

        return $this->runPostUpdateTasks($log, 'git');
    }

    /**
     * Update via ZIP download from GitHub.
     *
     * @param array<string> &$log
     * @return array{success: bool, method: string, message: string, log: array<string>}
     */
    private function performZipUpdate(array &$log): array
    {
        $log[] = '🔍 Metode instalasi: ZIP (tanpa Git)';

        $updateInfo = $this->checkForUpdate();

        if ($updateInfo === null || empty($updateInfo['url'])) {
            $log[] = '⚠️ Tidak dapat mengambil info update dari GitHub.';
            return [
                'success' => false,
                'method' => 'zip',
                'message' => 'Gagal memeriksa update terbaru dari GitHub.',
                'log' => $log,
            ];
        }

        if (! $updateInfo['has_update']) {
            $log[] = 'ℹ️ Versi saat ini sudah yang terbaru (' . $this->getCurrentVersion() . ').';
            return [
                'success' => true,
                'method' => 'zip',
                'message' => 'Aplikasi sudah menggunakan versi terbaru.',
                'log' => $log,
            ];
        }

        $log[] = '📥 Mengunduh versi ' . $updateInfo['tag'] . '...';

        $tempDir = storage_path('app/update-temp');
        $zipPath = $tempDir . '/update.zip';

        File::ensureDirectoryExists($tempDir);

        // Download ZIP
        $response = Http::timeout(120)
            ->withHeaders(['Accept' => 'application/vnd.github.v3+json'])
            ->withOptions(['sink' => $zipPath])
            ->get($updateInfo['url']);

        if (! file_exists($zipPath) || filesize($zipPath) < 1024) {
            $log[] = '❌ Gagal mengunduh file update.';
            File::deleteDirectory($tempDir);
            return [
                'success' => false,
                'method' => 'zip',
                'message' => 'Download file update gagal.',
                'log' => $log,
            ];
        }

        $log[] = '✅ Download selesai (' . number_format(filesize($zipPath) / 1024, 1) . ' KB)';
        $log[] = '📦 Mengekstrak file...';

        // Extract ZIP
        $extractDir = $tempDir . '/extracted';
        $zip = new ZipArchive();

        if ($zip->open($zipPath) !== true) {
            $log[] = '❌ Gagal membuka file ZIP.';
            File::deleteDirectory($tempDir);
            return [
                'success' => false,
                'method' => 'zip',
                'message' => 'File ZIP rusak atau tidak valid.',
                'log' => $log,
            ];
        }

        $zip->extractTo($extractDir);
        $zip->close();

        // GitHub ZIPs contain a root folder like "Jawara46-sik-abc1234/"
        $dirs = File::directories($extractDir);
        $sourceDir = ! empty($dirs) ? $dirs[0] : $extractDir;

        $log[] = '✅ Ekstraksi selesai.';
        $log[] = '🔄 Menyalin file baru...';

        // Protected paths — never overwrite
        $protectedPaths = [
            '.env',
            'storage',
            'vendor',
            'node_modules',
            'WAPI/auth_info',
            'wapi/auth_info',
        ];

        $this->syncDirectory($sourceDir, base_path(), $protectedPaths, $log);

        $log[] = '✅ File berhasil disinkronkan.';

        // Cleanup
        File::deleteDirectory($tempDir);
        $log[] = '🧹 File sementara dibersihkan.';

        return $this->runPostUpdateTasks($log, 'zip');
    }

    /**
     * Copy files from source to destination, skipping protected paths.
     *
     * @param string $source
     * @param string $destination
     * @param array<string> $protectedPaths
     * @param array<string> &$log
     */
    private function syncDirectory(string $source, string $destination, array $protectedPaths, array &$log): void
    {
        $items = File::allFiles($source);
        $copied = 0;
        $skipped = 0;

        foreach ($items as $file) {
            $relativePath = $file->getRelativePathname();

            $isProtected = false;
            foreach ($protectedPaths as $pp) {
                if (str_starts_with($relativePath, $pp)) {
                    $isProtected = true;
                    break;
                }
            }

            if ($isProtected) {
                $skipped++;
                continue;
            }

            $targetPath = $destination . '/' . $relativePath;
            $targetDir = dirname($targetPath);

            if (! is_dir($targetDir)) {
                File::makeDirectory($targetDir, 0755, true);
            }

            File::copy($file->getRealPath(), $targetPath);
            $copied++;
        }

        $log[] = "   Disalin: {$copied} file, Dilewati: {$skipped} file (dilindungi)";
    }

    /**
     * Run post-update commands (composer, migrate, cache clear).
     *
     * @param array<string> &$log
     * @param string $method
     * @return array{success: bool, method: string, message: string, log: array<string>}
     */
    private function runPostUpdateTasks(array &$log, string $method): array
    {
        $log[] = '⚙️ Menjalankan tugas pasca-update...';

        // Composer install
        $log[] = '   📦 composer install --no-dev --optimize-autoloader...';
        $output = [];
        exec('cd ' . escapeshellarg(base_path()) . ' && composer install --no-dev --optimize-autoloader 2>&1', $output);
        $log = array_merge($log, array_map(fn ($l) => '   ' . $l, array_slice($output, -3)));

        // Migrate
        $log[] = '   🗃️ php artisan migrate --force...';
        $output = [];
        exec('cd ' . escapeshellarg(base_path()) . ' && php artisan migrate --force 2>&1', $output);
        $log = array_merge($log, array_map(fn ($l) => '   ' . $l, $output));

        // Clear cache
        $log[] = '   🧹 php artisan optimize:clear...';
        $output = [];
        exec('cd ' . escapeshellarg(base_path()) . ' && php artisan optimize:clear 2>&1', $output);

        $log[] = '🎉 Update selesai!';

        return [
            'success' => true,
            'method' => $method,
            'message' => 'Aplikasi berhasil diperbarui.',
            'log' => $log,
        ];
    }

    /**
     * Get system environment info for the About page.
     *
     * @return array<string, string>
     */
    public function getEnvironmentInfo(): array
    {
        $nodeVersion = 'N/A';
        try {
            $output = [];
            $paths = ['/opt/homebrew/bin/node', '/usr/local/bin/node', '/usr/bin/node', 'node'];
            foreach ($paths as $bin) {
                exec(escapeshellarg($bin) . ' --version 2>/dev/null', $output);
                if (! empty($output) && str_starts_with(trim($output[0]), 'v')) {
                    $nodeVersion = trim($output[0]);
                    break;
                }
                $output = [];
            }

            // Fallback: ask the running WAPI gateway
            if ($nodeVersion === 'N/A') {
                $wapiUrl = config('services.wapi.url', 'http://127.0.0.1:3000');
                $response = @file_get_contents("{$wapiUrl}/health", false, stream_context_create([
                    'http' => ['timeout' => 2],
                ]));
                if ($response) {
                    $data = json_decode($response, true);
                    $nodeVersion = $data['node_version'] ?? 'N/A';
                }
            }
        } catch (Throwable) {
            // ignore
        }

        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'node_version' => $nodeVersion,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'CLI',
            'install_method' => $this->getInstallMethod(),
            'timezone' => config('app.timezone', 'UTC'),
            'locale' => app()->getLocale(),
        ];
    }
}
