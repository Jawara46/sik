<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Events\BeforeExport;

class GradesTemplateExport implements WithMultipleSheets, WithEvents
{
    /**
     * @param array<int, GradesTemplateSheetExport> $sheets
     */
    public function __construct(
        private readonly array $sheets,
    ) {
    }

    /**
     * @return array<int, GradesTemplateSheetExport>
     */
    public function sheets(): array
    {
        return $this->sheets;
    }

    /**
     * @return array<class-string, callable>
     */
    public function registerEvents(): array
    {
        return [
            BeforeExport::class => static function (BeforeExport $event): void {
                $properties = $event->writer->getDelegate()->getProperties();
                $properties
                    ->setCreator('Yazid Digital | 081311112309')
                    ->setLastModifiedBy('Yazid Digital | 081311112309')
                    ->setCompany('Yazid Digital')
                    ->setManager('Yazid Digital')
                    ->setDescription('Template nilai SIK-T dikembangkan oleh Yazid Digital.')
                    ->setSubject('Template Manajemen Nilai SIK-T')
                    ->setTitle('Template Nilai SIK-T');
            },
        ];
    }
}
