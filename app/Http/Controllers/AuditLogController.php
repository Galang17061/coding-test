<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::query();

        if ($request->filled('table_name')) {
            $query->where('table_name', $request->get('table_name'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->get('action'));
        }

        return response()->json([
            'logs' => $query->latest()->limit(100)->get()
        ]);
    }

    public function tables()
    {
        $tables = AuditLog::select('table_name')->distinct()->pluck('table_name');
        return response()->json(['tables' => $tables]);
    }
}
