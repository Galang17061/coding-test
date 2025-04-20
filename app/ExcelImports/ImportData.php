<?php

namespace App\ExcelImports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
class ImportData implements WithMultipleSheets
{
    public function sheets(): array
    {
        // Dynamically return a new instance for each sheet
        return [
            'Categories' => new CategorySheetImport(),
            'Products' => new ProductSheetImport(),
            'Orders' => new OrderSheetImport(),
            'Order Items' => new OrderItemSheetImport(),
        ];
    }
}
