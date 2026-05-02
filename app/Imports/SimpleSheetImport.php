<?php

declare(strict_types=1);

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class SimpleSheetImport implements ToCollection
{
    private Collection $rows;

    public function __construct()
    {
        $this->rows = collect();
    }

    public function collection(Collection $collection): void
    {
        $this->rows = $collection;
    }

    public function rows(): Collection
    {
        return $this->rows;
    }
}

