<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('category_id')->constrained('categories');
            $table->uuid()->default(Str::uuid()->toString())->unique();
            $table->timestamp('recorded_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_categories');
    }
};
