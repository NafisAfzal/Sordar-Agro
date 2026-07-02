<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            // Null seller_id = listed directly by the marketplace/admin.
            $table->foreignId('seller_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();

            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('thumbnail')->nullable();

            // Fish are sold AS PAIRS: one unit of stock = 2 fish. Non-fish
            // products (plants, foods, equipment) use a single variant.
            $table->boolean('is_fish')->default(false);

            // Browsing/filter metadata.
            $table->unsignedInteger('min_tank_size_litres')->nullable();
            $table->enum('temperament', ['peaceful', 'semi-aggressive', 'aggressive'])->nullable();

            // Approval workflow: seller submissions stay 'pending' until an
            // admin approves (visible) or rejects (with feedback).
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('admin_feedback')->nullable();
            $table->boolean('is_featured')->default(false);

            $table->timestamps();

            $table->index(['status', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
