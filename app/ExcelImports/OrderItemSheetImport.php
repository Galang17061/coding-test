<?php

namespace App\ExcelImports;

use App\Models\AuditLog;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class OrderItemSheetImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsOnError
{
    use SkipsFailures;
    use SkipsErrors;

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $productId = $this->productId($row['product']);
            $orderId = $row['order_id'];
            $quantity = $row['quantity'];
            $unitPrice = $row['unit_price'];

            if ($productId === null) {
                Throw new Exception('Product not found');
            }

            $orderItem = OrderItem::create([
                'order_id' => $orderId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'created_at' => now()->format('Y-m-d h:i:s'),
                'updated_at' => now()->format('Y-m-d h:i:s'),
            ]);

            AuditLog::create([
                'id' => Str::uuid(),
                'table_name' => 'order_items',
                'record_id' => $orderItem->id,
                'action' => 'created',
                'new_values' => json_encode($orderItem->toArray()),
                'user_id' => auth()->id(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->header('User-Agent'),
            ]);
        }
    }

    private function productId($productTitle)
    {
        return Product::where('title', $productTitle)->first()?->id;
    }

    private function userId($username)
    {
        return User::where('name', $username)->first()?->id;
    }

    public function rules(): array
    {
        return [
            'order_id' => 'required|int',
            'product' => 'required|string|max:255',
            'quantity' => 'required|int',
            'unit_price' => 'required|int',
        ];
    }
}
