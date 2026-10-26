<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('payment_submitted_at')->nullable()->after('transaction_id');
            $table->timestamp('payment_verified_at')->nullable()->after('payment_submitted_at');
            $table->foreignId('payment_verified_by')->nullable()->after('payment_verified_at')
                ->constrained('users')->nullOnDelete();

            // A TrxID identifies one real mobile-money transfer, so it can only
            // belong to one order. The application validates this too; the
            // index closes the race between two concurrent submissions.
            $table->unique('transaction_id');
            $table->index(['status', 'payment_status']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['transaction_id']);
            $table->dropIndex(['status', 'payment_status']);
            $table->dropConstrainedForeignId('payment_verified_by');
            $table->dropColumn(['payment_submitted_at', 'payment_verified_at']);
        });
    }
};
