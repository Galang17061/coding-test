<?php

namespace App\ExcelImports;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Exception;
use Illuminate\Support\Collection;
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

            OrderItem::create([
                'order_id' => $orderId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'created_at' => now()->format('Y-m-d h:i:s'),
                'updated_at' => now()->format('Y-m-d h:i:s'),
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
