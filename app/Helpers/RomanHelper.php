<?php

declare(strict_types=1);

namespace App\Helpers;

class RomanHelper
{
    /**
     * Konversi angka bulan menjadi angka Romawi.
     */
    public static function convertMonthToRoman(int $month): string
    {
        $map = [
            1 => 'I',
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            5 => 'V',
            6 => 'VI',
            7 => 'VII',
            8 => 'VIII',
            9 => 'IX',
            10 => 'X',
            11 => 'XI',
            12 => 'XII',
        ];

        return $map[$month] ?? '';
    }
}
