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
        Schema::table('order_items', function (Blueprint $table) {
            // Snapshotted from products.profit_share_amount at the moment the
            // order is placed — never looked up live, so historical orders
            // stay accurate even if the seller later changes their share.
            // Existing rows default to 0: that amount was genuinely never
            // tracked for orders placed before this column existed, so 0
            // honestly reflects "unknown," not a fabricated historical value.
            $table->decimal('marketplace_share_amount', 10, 2)->default(0)->after('price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('marketplace_share_amount');
        });
    }
};
