<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AnnouncementService
{
    public function getAnnouncementAt(): ?CarbonImmutable
    {
        $value = $this->getAnnouncementDateValue();

        if ($value === null || $value === '') {
            return null;
        }

        $timezone = (string) config('sik.announcement_timezone', 'Asia/Jakarta');

        try {
            return CarbonImmutable::parse($value, $timezone)->setTimezone($timezone);
        } catch (Throwable) {
            return null;
        }
    }

    public function saveAnnouncementAt(CarbonInterface $announcementAt): void
    {
        if (!Schema::hasTable('settings')) {
            return;
        }

        $timezone = (string) config('sik.announcement_timezone', 'Asia/Jakarta');
        $value = $announcementAt->copy()->setTimezone($timezone)->format('Y-m-d H:i:s');

        DB::table('settings')->updateOrInsert(
            ['key' => 'announcement_date'],
            [
                'value' => $value,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    private function getAnnouncementDateValue(): ?string
    {
        $fallback = config('sik.announcement_date');

        try {
            if (!Schema::hasTable('settings')) {
                return $fallback;
            }

            $value = DB::table('settings')
                ->where('key', 'announcement_date')
                ->value('value');

            return is_string($value) && $value !== '' ? $value : $fallback;
        } catch (Throwable) {
            return $fallback;
        }
    }
}
