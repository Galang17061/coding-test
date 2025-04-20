<?php

namespace App\ExcelImports;

use App\Models\Category;
use App\Models\User;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;
use Throwable;

class CategorySheetImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsOnError
{
    use SkipsFailures;
    use SkipsErrors;
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $parentId = '';
            if ($row['parent'] !== null) {
                $parentId = $this->categoryId($row['parent']);
            }
            $createdById = $this->userId($row['created_by']);
            $updatedById = $this->userId($row['updated_by']);
            $deletedById = $this->userId($row['deleted_by']);

            if (
                $createdById === null ||
                $updatedById === null
            ) {
                Throw new Exception('User not found');
            }

            if ($row['deleted_at'] !== null && $deletedById === null) {
                Throw new Exception('Deleted by user not found');
            }

            Category::create([
                'name' => $row['name'],
                'slug' => Str::slug($row['slug']),
                'active' => $row['active'],
                'parent' => $parentId,
                'created_by' => $createdById,
                'created_at' => now()->format('Y-m-d h:i:s'),
                'updated_by' => $updatedById,
                'updated_at' => now()->format('Y-m-d h:i:s'),
                'deleted_at' => $row['deleted_at'] ?? null,
                'deleted_by' => $deletedById,
            ]);
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
            'name' => 'string|required|max:255',
            'slug' => 'string|required|max:255',
            'active' => 'boolean',
            'parent' => 'nullable',
            'created_by' => 'required',
            'updated_by' => 'required',
            'deleted_at' => 'nullable|date',
            'deleted_by' => 'nullable|required_with:deleted_at',
        ];
    }

    public function onFailure(Failure ...$failures)
    {
        Log::info('fail : ',[$failures]);
        // Handle the failures how you'd like.
    }

    public function onError(Throwable $e)
    {
        Log::info('error : ',[$e]);
        // TODO: Implement onError() method.
    }
}
