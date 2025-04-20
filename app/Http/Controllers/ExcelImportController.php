<?php

namespace App\Http\Controllers;

use App\Jobs\ImportExcelJob;
use Illuminate\Http\Request;

class ExcelImportController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $filePath = $request->file('file')->store('temp');

        ImportExcelJob::dispatch($filePath);

        return response()->json(['message' => 'Import process started. You will be notified once it is completed.']);
    }
}
