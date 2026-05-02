<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Protection;

class GradesTemplateSheetExport implements FromArray, WithTitle, ShouldAutoSize, WithEvents
{
    /**
     * @param array<int, string> $headings
     * @param array<int, array<int, mixed>> $rows
     */
    public function __construct(
        private readonly string $title,
        private readonly array $headings,
        private readonly array $rows,
        private readonly int $lockedColumns = 3,
    ) {
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        return [
            $this->headings,
            ...$this->rows,
        ];
    }

    public function title(): string
    {
        return $this->title;
    }

    /**
     * @return array<class-string, callable>
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $highestColumn = $sheet->getHighestColumn();
                $highestRow = max(1, $sheet->getHighestRow());
                $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);
                $firstEditableColumnIndex = min($this->lockedColumns + 1, $highestColumnIndex);
                $firstEditableColumn = Coordinate::stringFromColumnIndex($firstEditableColumnIndex);

                $sheet->getStyle('A1:' . $highestColumn . '1')->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'EEF2FF'],
                    ],
                ]);

                if ($highestRow >= 2 && $firstEditableColumnIndex <= $highestColumnIndex) {
                    $sheet->getStyle($firstEditableColumn . '2:' . $highestColumn . $highestRow)
                        ->getProtection()
                        ->setLocked(Protection::PROTECTION_UNPROTECTED);
                }

                $sheet->freezePane(Coordinate::stringFromColumnIndex($firstEditableColumnIndex) . '2');
                $sheet->getProtection()
                    ->setSheet(true)
                    ->setSort(true)
                    ->setInsertRows(false)
                    ->setFormatCells(true)
                    ->setPassword('sik-t-yazid');
            },
        ];
    }
}
