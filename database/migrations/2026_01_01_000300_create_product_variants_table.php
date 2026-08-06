<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            // Fish products carry three rows (small/medium/large); non-fish
            // products carry one row with size = 'standard'.
            $table->enum('size', ['small', 'medium', 'large', 'standard'])->default('standard');

            $table->string('sku')->nullable()->unique();
            $table->decimal('price', 10, 2);

            // For fish this is a PAIR count (stock of 5 = 5 pairs = 10 fish).
            $table->unsignedInteger('stock')->default(0);
            $table->text('size_description')->nullable();

            $table->timestamps();

            $table->unique(['product_id', 'size']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
