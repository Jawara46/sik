<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Process;

class DatabaseBackupService
{
    private string $disk = 'local';
    private string $directory = 'backups/database';

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function list(): Collection
    {
        return collect(Storage::disk($this->disk)->files($this->directory))
            ->filter(fn (string $path): bool => str_ends_with($path, '.sql'))
            ->map(function (string $path): array {
                /** @var Filesystem $disk */
                $disk = Storage::disk($this->disk);

                return [
                    'path' => $path,
                    'filename' => basename($path),
                    'size' => $disk->size($path),
                    'last_modified' => $disk->lastModified($path),
                ];
            })
            ->sortByDesc('last_modified')
            ->values();
    }

    /**
     * @return array{path:string,filename:string,size:int}
     */
    public function create(): array
    {
        $binary = $this->resolveMysqldumpBinary();
        $filename = sprintf('sik-backup-%s.sql', now()->format('Ymd-His'));
        $relativePath = $this->directory . '/' . $filename;
        $absolutePath = Storage::disk($this->disk)->path($relativePath);

        if (!is_dir(dirname($absolutePath))) {
            mkdir(dirname($absolutePath), 0775, true);
        }

        $command = [
            $binary,
            sprintf('--host=%s', (string) config('database.connections.mysql.host')),
            sprintf('--port=%s', (string) config('database.connections.mysql.port')),
            sprintf('--user=%s', (string) config('database.connections.mysql.username')),
            sprintf('--password=%s', (string) config('database.connections.mysql.password')),
            '--single-transaction',
            '--quick',
            '--skip-lock-tables',
            '--result-file=' . $absolutePath,
            (string) config('database.connections.mysql.database'),
        ];

        $socket = (string) config('database.connections.mysql.unix_socket', '');
        if ($socket !== '') {
            $command[] = '--socket=' . $socket;
        }

        $process = new Process($command, base_path(), [
            'PATH' => getenv('PATH') ?: '',
        ]);
        $process->setTimeout(60);
        $process->mustRun();

        return [
            'path' => $relativePath,
            'filename' => $filename,
            'size' => Storage::disk($this->disk)->size($relativePath),
        ];
    }

    public function exists(string $filename): bool
    {
        return Storage::disk($this->disk)->exists($this->directory . '/' . basename($filename));
    }

    public function path(string $filename): string
    {
        return Storage::disk($this->disk)->path($this->directory . '/' . basename($filename));
    }

    private function resolveMysqldumpBinary(): string
    {
        $candidates = [
            '/Applications/MAMP/Library/bin/mysqldump',
            '/Applications/MAMP/Library/bin/mariadb-dump',
            'mysqldump',
        ];

        foreach ($candidates as $candidate) {
            if ($candidate === 'mysqldump') {
                return $candidate;
            }

            if (is_file($candidate) && is_executable($candidate)) {
                return $candidate;
            }
        }

        throw new RuntimeException('Binary mysqldump tidak ditemukan.');
    }
}
