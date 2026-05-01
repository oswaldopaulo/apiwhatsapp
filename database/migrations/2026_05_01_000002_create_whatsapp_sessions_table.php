<?php

use App\Enums\WhatsAppSessionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('provider')->default('fake');
            $table->string('status')->default(WhatsAppSessionStatus::Connecting->value)->index();
            $table->string('phone_number')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->unsignedTinyInteger('risk_score')->default(10);
            $table->json('metadata')->nullable();
            $table->text('encrypted_credentials')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'name']);
            $table->unique(['tenant_id', 'phone_number']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'last_activity_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_sessions');
    }
};
