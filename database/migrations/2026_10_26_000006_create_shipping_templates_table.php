<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shipping_templates', function (Blueprint $table) {
            $table->id(); $table->string('name')->unique(); $table->string('slug')->unique();
            $table->json('country_codes'); $table->string('currency', 3)->default('USD');
            $table->decimal('base_rate', 12, 2)->default(0); $table->decimal('per_lb_rate', 12, 2)->default(0);
            $table->unsignedSmallInteger('handling_days')->default(3); $table->string('delivery_method', 30)->default('courier');
            $table->boolean('is_active')->default(true); $table->timestamps();
        });
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('shipping_template_id')->nullable()->after('shipping_template')->constrained('shipping_templates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shipping_template_id');
        });
        Schema::dropIfExists('shipping_templates');
    }
};
