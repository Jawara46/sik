<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Major;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class MajorManagementService
{
    /**
     * @param array<string, mixed> $payload
     */
    public function create(int $schoolId, array $payload): Major
    {
        return Major::query()->create([
            'school_id' => $schoolId,
            'name' => (string) $payload['name'],
            'code' => strtoupper((string) $payload['code']),
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function update(Major $major, array $payload): Major
    {
        $major->fill([
            'name' => (string) $payload['name'],
            'code' => strtoupper((string) $payload['code']),
        ]);
        $major->save();

        return $major->refresh();
    }

    public function delete(Major $major): void
    {
        $major->delete();
    }

    public function findByCode(int $schoolId, string $code): ?Major
    {
        return Major::query()
            ->where('school_id', $schoolId)
            ->where('code', strtoupper($code))
            ->first();
    }

    /**
     * @return EloquentCollection<int, Major>
     */
    public function forSchool(int $schoolId): EloquentCollection
    {
        return Major::query()
            ->where('school_id', $schoolId)
            ->orderBy('name')
            ->get();
    }

    public function assertSameSchool(Major $major, int $schoolId): void
    {
        if ((int) $major->school_id !== (int) $schoolId) {
            throw new ModelNotFoundException();
        }
    }
}

