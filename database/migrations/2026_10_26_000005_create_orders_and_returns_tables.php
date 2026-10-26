<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id(); $table->string('order_number', 32)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('customer_name'); $table->string('email')->index(); $table->string('phone', 40)->nullable();
            $table->string('currency', 3)->default('USD'); $table->decimal('subtotal', 12, 2);
            $table->decimal('shipping_total', 12, 2)->default(0); $table->decimal('grand_total', 12, 2);
            $table->string('payment_provider', 40)->nullable(); $table->string('payment_status', 30)->default('pending');
            $table->string('order_status', 30)->default('pending_payment'); $table->string('payment_reference')->nullable();
            $table->string('courier_name')->nullable(); $table->string('tracking_number')->nullable();
            $table->string('shipping_country', 2); $table->string('shipping_state')->nullable();
            $table->string('shipping_city'); $table->string('shipping_postal_code', 30)->nullable();
            $table->text('shipping_address'); $table->text('customer_notes')->nullable();
            $table->timestamp('paid_at')->nullable(); $table->timestamp('shipped_at')->nullable(); $table->timestamps();
            $table->index(['order_status', 'created_at']); $table->index(['payment_status', 'created_at']);
        });
        Schema::create('order_items', function (Blueprint $table) {
            $table->id(); $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name'); $table->string('sku', 80)->nullable();
            $table->unsignedInteger('quantity'); $table->decimal('unit_price', 12, 2);
            $table->decimal('line_total', 12, 2); $table->string('currency', 3)->default('USD');
            $table->json('attributes')->nullable(); $table->timestamps();
        });
        Schema::create('order_returns', function (Blueprint $table) {
            $table->id(); $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason', 100); $table->text('details')->nullable();
            $table->string('status', 30)->default('requested'); $table->text('admin_notes')->nullable();
            $table->timestamps(); $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_returns');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
