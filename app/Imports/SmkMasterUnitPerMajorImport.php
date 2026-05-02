<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Major;
use App\Models\SmkUnit;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class SmkMasterUnitPerMajorImport implements ToCollection, WithStartRow
{
    private Major $major;

    public function __construct(Major $major)
    {
        $this->major = $major;
    }

    public function startRow(): int
    {
        return 4; // Start from row 4, because rows 1-3 are headers/info strings
    }

    /**
     * @param Collection<int, \Illuminate\Support\Collection<int, mixed>> $rows
     */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            // Usually, $row[0] is Kode Unit, $row[1] is Judul Unit
            $kodeUnit = trim((string) ($row[0] ?? ''));
            $judulUnit = trim((string) ($row[1] ?? ''));

            // Only process rows that have actual data
            if ($kodeUnit === '' || $judulUnit === '') {
                continue;
            }

            SmkUnit::updateOrCreate(
                [
                    'major_id' => $this->major->id,
                    'kode_unit' => $kodeUnit,
                ],
                [
                    'judul_unit' => $judulUnit
                ]
            );
        }
    }
}
