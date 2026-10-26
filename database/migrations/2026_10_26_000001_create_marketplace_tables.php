<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable(); $table->string('password');
            $table->boolean('is_admin')->default(false); $table->string('preferred_locale', 10)->default('en');
            $table->string('preferred_currency', 3)->default('USD'); $table->rememberToken(); $table->timestamps();
        });
        Schema::create('categories', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('slug')->unique();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->text('description')->nullable(); $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0); $table->timestamps();
            $table->index(['parent_id', 'is_active']);
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id(); $table->string('product_code', 40)->unique(); $table->string('name');
            $table->string('slug')->unique(); $table->longText('description')->nullable();
            $table->longText('specification')->nullable(); $table->string('brand_name')->nullable();
            $table->string('country_of_origin', 100)->nullable();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->unsignedBigInteger('sub_category_id')->nullable(); $table->unsignedBigInteger('sub_sub_category_id')->nullable();
            $table->decimal('base_price', 12, 2)->default(0); $table->decimal('list_price', 12, 2)->nullable();
            $table->decimal('retail_price', 12, 2)->default(0); $table->boolean('b2b_enabled')->default(false);
            $table->boolean('group_buy_enabled')->default(false); $table->unsignedInteger('stock')->default(0);
            $table->json('selling_countries')->nullable(); $table->string('upc', 32)->nullable();
            $table->string('gtin', 32)->nullable(); $table->string('sku', 80)->unique();
            $table->string('qr_code_number', 80)->unique(); $table->decimal('product_weight_lb', 10, 3)->nullable();
            $table->decimal('package_weight_lb', 10, 3)->nullable(); $table->decimal('liters', 10, 3)->nullable();
            $table->json('attributes')->nullable(); $table->boolean('has_variations')->default(true);
            $table->string('shipping_template')->nullable(); $table->unsignedSmallInteger('handling_time_days')->default(2);
            $table->string('delivery_method')->nullable(); $table->string('supplier_name')->nullable();
            $table->text('supplier_contact')->nullable(); $table->json('tracking_numbers')->nullable();
            $table->string('courier_name')->nullable(); $table->text('return_address')->nullable();
            $table->longText('return_policy')->nullable(); $table->text('warranty')->nullable();
            $table->string('status', 20)->default('draft'); $table->boolean('featured')->default(false);
            $table->timestamps(); $table->softDeletes();
            $table->index(['status', 'featured']); $table->index(['category_id', 'status']);
        });
        Schema::create('product_media', function (Blueprint $table) {
            $table->id(); $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20); $table->string('path'); $table->string('alt_text')->nullable();
            $table->unsignedInteger('sort_order')->default(0); $table->timestamps();
        });
        Schema::create('product_price_tiers', function (Blueprint $table) {
            $table->id(); $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('min_quantity'); $table->unsignedInteger('max_quantity')->nullable();
            $table->decimal('unit_price', 12, 2); $table->string('currency', 3)->default('USD');
            $table->string('label')->nullable(); $table->timestamps(); $table->index(['product_id', 'min_quantity']);
        });
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id(); $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name'); $table->string('sku')->unique(); $table->json('attributes')->nullable();
            $table->unsignedInteger('stock')->default(0); $table->decimal('price_adjustment', 12, 2)->default(0);
            $table->string('barcode')->nullable(); $table->timestamps();
        });
        Schema::create('group_buy_campaigns', function (Blueprint $table) {
            $table->id(); $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('title'); $table->decimal('unit_price', 12, 2); $table->string('currency', 3)->default('USD');
            $table->unsignedInteger('target_participants'); $table->unsignedInteger('current_participants')->default(0);
            $table->timestamp('starts_at')->nullable(); $table->timestamp('ends_at')->nullable();
            $table->string('status', 20)->default('draft'); $table->timestamps();
            $table->index(['status', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_buy_campaigns');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_price_tiers');
        Schema::dropIfExists('product_media');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('users');
    }
};
