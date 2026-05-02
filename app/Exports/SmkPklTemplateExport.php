<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SmkPklTemplateExport implements FromCollection, WithHeadings, WithColumnWidths, WithStyles
{
    public function __construct(private Collection $data)
    {
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'NISN',
            'Nama Siswa',
            'Kode Jurusan',
            'Tempat PKL / Industri',
            'Nilai PKL',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 35,
            'C' => 15,
            'D' => 40,
            'E' => 15,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
