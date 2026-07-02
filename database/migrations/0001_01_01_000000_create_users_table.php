<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            // Role-based access control. Admins are seeded only; sellers are
            // created by an admin; customers self-register.
            $table->enum('role', ['customer', 'seller', 'admin'])->default('customer');

            // Sellers created by an admin must change the temporary password
            // on first login. Customers/admins are created with this false.
            $table->boolean('must_change_password')->default(false);

            // Allows an admin to suspend/activate any account.
            $table->boolean('is_active')->default(true);

            // The admin who provisioned this seller (nullable for everyone else).
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
