<?php

declare(strict_types=1);

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SmkMasterUnitExport implements WithMultipleSheets
{
    use Exportable;

    private Collection $majors;

    /**
     * @param Collection<int, \App\Models\Major> $majors
     */
    public function __construct(Collection $majors)
    {
        $this->majors = $majors;
    }

    /**
     * @return array<int, \Maatwebsite\Excel\Concerns\WithTitle>
     */
    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->majors as $major) {
            $sheets[] = new SmkMasterUnitPerMajorSheet($major);
        }

        return $sheets;
    }
}
