<?php

namespace App\ExcelImports;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Exception;

class ProductSheetImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsOnError
{
    use SkipsFailures;
    use SkipsErrors;

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $createdById = $this->userId($row['created_by']);
            $updatedById = $this->userId($row['updated_by']);
            $deletedById = $this->userId($row['deleted_by']);
            $categoryId = $this->categoryId($row['product_categories']);

            if (
                $createdById === null ||
                $updatedById === null
            ) {
                Throw new Exception('User not found');
            }

            if ($row['deleted_at'] !== null && $deletedById === null) {
                Throw new Exception('Deleted by user not found');
            }

            $product = Product::create([
                'title' => $row['title'],
                'slug' => Str::slug($row['slug']),
                'description' => $row['description'],
                'price' => $row['price'],
                'quantity' => $row['quantity'],
                'published' => $row['published'],
                'created_by' => $createdById,
                'created_at' => now()->format('Y-m-d h:i:s'),
                'updated_by' => $updatedById,
                'updated_at' => now()->format('Y-m-d h:i:s'),
                'deleted_at' => $row['deleted_at'] ?? null,
                'deleted_by' => $deletedById,
            ]);

            if ($product) {
                ProductCategory::insert([
                    'category_id' => $categoryId,
                    'product_id' => $product->id,
                ]);
            }
        }
    }

    private function categoryId($parent)
    {
        return Category::where('name', $parent)->first()?->id;
    }

    private function userId($username)
    {
        return User::where('name', $username)->first()?->id;
    }

    public function rules(): array
    {
        return [
            'title' => 'string|required|max:255',
            'slug' => 'string|required|max:255',
            'description' => 'nullable|max:255',
            'price' => 'int|required',
            'quantity' => 'int|nullable',
            'published' => 'boolean|required',
            'product_categories' => 'required',
            'created_by' => 'required',
            'updated_by' => 'required',
            'deleted_at' => 'nullable|date',
            'deleted_by' => 'nullable|required_with:deleted_at',
        ];
    }
}
