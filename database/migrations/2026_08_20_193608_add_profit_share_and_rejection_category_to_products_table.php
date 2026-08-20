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
        Schema::table('products', function (Blueprint $table) {
            // DB-level default of 0 only exists so existing rows don't break
            // this migration; every code path that creates/updates a product
            // enforces "required, greater than 0" at the validation layer.
            $table->decimal('profit_share_amount', 10, 2)->default(0)->after('temperament');

            // Only populated when status = 'rejected'.
            $table->enum('rejection_reason_category', [
                'profit_share', 'price', 'quantity', 'product_quality', 'other',
            ])->nullable()->after('admin_feedback');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['profit_share_amount', 'rejection_reason_category']);
        });
    }
};
