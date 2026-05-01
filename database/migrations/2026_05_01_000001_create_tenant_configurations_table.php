<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_configurations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained('tenants')->cascadeOnDelete();
            $table->string('queue_driver')->default('default');
            $table->boolean('redis_enabled')->default(false);
            $table->boolean('anti_ban_enabled')->default(true);
            $table->unsignedSmallInteger('delay_min_seconds')->default(3);
            $table->unsignedSmallInteger('delay_max_seconds')->default(12);
            $table->unsignedSmallInteger('max_messages_per_minute')->default(20);
            $table->unsignedInteger('max_daily_messages')->default(1000);
            $table->string('webhook_url')->nullable();
            $table->text('webhook_secret')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'queue_driver']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_configurations');
    }
};
