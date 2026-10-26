<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('group_buy_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('group_buy_campaigns')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['campaign_id', 'user_id']);
            $table->index(['campaign_id', 'order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_buy_participants');
    }
};
