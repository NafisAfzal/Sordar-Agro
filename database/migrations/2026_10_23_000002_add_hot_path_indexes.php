<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Composite indexes for the queries hit on every storefront and dashboard
 * request. Foreign-key columns are already indexed by MySQL; these cover the
 * filter + sort combinations that would otherwise fall back to filesorts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Customer order history: where user_id = ? order by created_at desc.
            $table->index(['user_id', 'created_at']);
            // Admin "latest orders" and analytics date-range scans.
            $table->index('created_at');
            // Admin status tabs: where status = ? order by created_at desc.
            $table->index(['status', 'created_at']);
            // Paid-sales aggregates: payment_status + status + date range.
            $table->index(['payment_status', 'status', 'created_at']);
        });

        Schema::table('products', function (Blueprint $table) {
            // Storefront catalogue: approved products, newest first.
            $table->index(['status', 'created_at']);
            // Seller workspace: own products, newest first.
            $table->index(['seller_id', 'created_at']);
        });

        Schema::table('community_submissions', function (Blueprint $table) {
            // Public feed + admin moderation tabs.
            $table->index(['status', 'created_at']);
        });

        Schema::table('care_guides', function (Blueprint $table) {
            // Published guides, newest first.
            $table->index('published_at');
        });

        Schema::table('users', function (Blueprint $table) {
            // Admin user/seller lists and dashboard role counts.
            $table->index(['role', 'created_at']);
        });

        Schema::table('wishlists', function (Blueprint $table) {
            // Restock notifications: where product_id = ? and notified = false.
            $table->index(['product_id', 'notified']);
        });
    }

    public function down(): void
    {
        Schema::table('wishlists', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'notified']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role', 'created_at']);
        });

        Schema::table('care_guides', function (Blueprint $table) {
            $table->dropIndex(['published_at']);
        });

        Schema::table('community_submissions', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
            $table->dropIndex(['seller_id', 'created_at']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['payment_status', 'status', 'created_at']);
            $table->dropIndex(['status', 'created_at']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['user_id', 'created_at']);
        });
    }
};
