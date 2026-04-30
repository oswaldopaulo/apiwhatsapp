<?php

use App\Enums\WhatsAppAccountStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whats_app_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('phone_number')->nullable();
            $table->string('provider')->default('default');
            $table->string('status')->default(WhatsAppAccountStatus::Pending->value)->index();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'phone_number']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whats_app_accounts');
    }
};
