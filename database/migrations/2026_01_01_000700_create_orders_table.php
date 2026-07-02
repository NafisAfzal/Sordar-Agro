<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->decimal('total', 10, 2);

            // Fulfilment status shown to the customer on the tracking page.
            $table->enum('status', ['processing', 'shipped', 'delivered', 'cancelled'])
                  ->default('processing');

            // Simulated payment.
            $table->enum('payment_method', ['bkash', 'nagad'])->nullable();
            $table->enum('payment_status', ['unpaid', 'paid', 'failed'])->default('unpaid');
            $table->string('transaction_id')->nullable();

            // Shipping + simulated courier tracking.
            $table->string('shipping_name');
            $table->string('shipping_phone');
            $table->text('shipping_address');
            $table->enum('courier', ['pathao', 'steadfast'])->nullable();
            $table->string('tracking_code')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
