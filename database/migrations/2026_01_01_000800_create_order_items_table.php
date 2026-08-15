<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            // Keep the variant link but also snapshot name/size/price so the
            // order remains accurate even if the product later changes.
            $table->foreignId('product_variant_id')->nullable()
                  ->constrained('product_variants')->nullOnDelete();
            $table->string('product_name');
            $table->string('variant_size');
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('quantity');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
