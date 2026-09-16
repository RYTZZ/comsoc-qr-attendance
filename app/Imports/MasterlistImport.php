<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class MasterlistImport implements ToArray, WithHeadingRow
{
    public function array(array $array): array
    {
        return $array;
    }
}
