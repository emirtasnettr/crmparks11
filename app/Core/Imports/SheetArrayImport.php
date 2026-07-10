<?php

namespace App\Core\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

final class SheetArrayImport implements ToArray
{
    /**
     * @param  array<int, array<int, mixed>>  $array
     * @return array<int, array<int, mixed>>
     */
    public function array(array $array): array
    {
        return $array;
    }
}
