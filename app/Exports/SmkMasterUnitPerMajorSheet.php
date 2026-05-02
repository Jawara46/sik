<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Major;
use App\Models\SmkUnit;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SmkMasterUnitPerMajorSheet implements FromQuery, WithTitle, WithHeadings, WithMapping, WithStyles
{
    private Major $major;

    public function __construct(Major $major)
    {
        $this->major = $major;
    }

    /**
     * @return EloquentBuilder<SmkUnit>
     */
    public function query()
    {
        return SmkUnit::query()
            ->where('major_id', $this->major->id)
            ->orderBy('kode_unit');
    }

    public function title(): string
    {
        // Excel worksheet title limits to 31 chars
        $code = str_replace([' ', '/', '\\', '?', '*', ':', '[' ,']'], '', $this->major->code);
        return substr(strtoupper($code), 0, 31);
    }

    public function headings(): array
    {
        return [
            [$this->major->name . ' (' . $this->major->code . ')'],
            ['JANGAN UBAH NAMA WORKSHEET (TAB DI BAWAH). Sistem membaca ID jurusan dari nama sheet tersebut.'],
            ['Kode Unit (SKKNI)', 'Judul Unit']
        ];
    }

    /**
     * @param SmkUnit $unit
     */
    public function map($unit): array
    {
        return [
            $unit->kode_unit,
            $unit->judul_unit,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        // Bold the first three heading rows
        $sheet->getStyle('A1:B3')->getFont()->setBold(true);

        // Merge the title and info rows to span across the 2 columns
        $sheet->mergeCells('A1:B1');
        $sheet->mergeCells('A2:B2');
        
        $sheet->getStyle('A2:B2')->getFont()->setItalic(true);
        $sheet->getStyle('A2:B2')->getFont()->getColor()->setARGB('FFFF0000'); // Red colored alert
        
        // Auto-sizing the columns doesn't always work perfectly through the trait natively,
        // but we can enforce minimum widths
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(80);

        return [];
    }
}
