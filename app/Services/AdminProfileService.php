<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminProfileService
{
    /**
     * @param array<string, mixed> $payload
     * @param array<string, UploadedFile|null> $uploadedFiles
     */
    public function updateProfile(User $admin, array $payload, array $uploadedFiles = []): User
    {
        return DB::transaction(function () use ($admin, $payload, $uploadedFiles): User {
            $avatarFile = $uploadedFiles['avatar'] ?? null;
            if ($avatarFile instanceof UploadedFile) {
                $newPath = $avatarFile->store('admin/avatars', 'public');
                $this->deleteOldAvatar((string) $admin->avatar, $newPath);
                $payload['avatar'] = $newPath;
            }

            $admin->fill([
                'name' => (string) ($payload['name'] ?? $admin->name),
                'email' => (string) ($payload['email'] ?? $admin->email),
                'nomor_wa' => $payload['nomor_wa'] ?? $admin->nomor_wa,
                'alamat' => $payload['alamat'] ?? $admin->alamat,
                'avatar' => $payload['avatar'] ?? $admin->avatar,
            ]);
            $admin->save();

            return $admin->refresh();
        });
    }

    public function updatePassword(User $admin, string $password): User
    {
        $admin->password = $password;
        $admin->save();

        return $admin->refresh();
    }

    public function updatePin(User $admin, string $pin): User
    {
        $admin->pin = $pin;
        $admin->save();

        return $admin->refresh();
    }

    public function deleteAvatar(User $admin): User
    {
        $oldPath = (string) $admin->avatar;

        if ($oldPath !== '' && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        $admin->avatar = null;
        $admin->save();

        return $admin->refresh();
    }

    private function deleteOldAvatar(string $oldPath, string $newPath): void
    {
        if ($oldPath === '' || $oldPath === $newPath) {
            return;
        }

        if (Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }
    }
}
