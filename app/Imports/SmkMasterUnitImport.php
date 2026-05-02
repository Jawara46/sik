<?php

declare(strict_types=1);

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Models\Major;

class SmkMasterUnitImport implements WithMultipleSheets
{
    private int $schoolId;

    public function __construct(int $schoolId)
    {
        $this->schoolId = $schoolId;
    }

    /**
     * Define the sheets that should be imported.
     * We dynamically look up the major codes based on the sheet names.
     * 
     * @return array<string, \Maatwebsite\Excel\Concerns\ToCollection>
     */
    public function sheets(): array
    {
        $majors = Major::where('school_id', $this->schoolId)->get();
        $sheets = [];

        foreach ($majors as $major) {
            $code = str_replace([' ', '/', '\\', '?', '*', ':', '[' ,']'], '', $major->code);
            $sheetName = substr(strtoupper($code), 0, 31);
            
            // We map the expected sheet name to its specific importer handler
            $sheets[$sheetName] = new SmkMasterUnitPerMajorImport($major);
        }

        return $sheets;
    }
}
