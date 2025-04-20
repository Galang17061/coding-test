<?php

namespace App\ExcelImports;

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class OrderSheetImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsOnError
{
    use SkipsFailures;
    use SkipsErrors;

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $order = Order::create([
                'id' => $row['order_id'],
                'total_price' => $row['total_price'],
                'status' => $row['status_paid'],
                'created_by' => $this->userId($row['created_by']),
                'created_at' => now()->format('Y-m-d h:i:s'),
                'updated_by' => $this->userId($row['updated_by']),
                'updated_at' => now()->format('Y-m-d h:i:s'),
                'uuid' => Str::uuid(),
            ]);

            AuditLog::create([
                'id' => Str::uuid(),
                'table_name' => 'orders',
                'record_id' => $order->id,
                'action' => 'deleted',
                'new_values' => json_encode($order->toArray()),
                'user_id' => auth()->id(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->header('User-Agent'),
            ]);
        }
    }

    private function userId($username)
    {
        return User::where('name', $username)->first()?->id;
    }

    public function rules(): array
    {
        return [
            'total_price' => 'required|numeric',
            'status_paid' => 'nullable|string|in:paid,unpaid',
            'created_by' => 'nullable|string',
            'updated_by' => 'nullable|string',
        ];
    }
}
