<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ai_mysql')->create('ai_request_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('api_key_id')->nullable();
            $table->string('provider');
            $table->string('model');
            $table->string('status');
            $table->boolean('is_stream')->default(false);
            $table->unsignedInteger('latency_ms')->nullable();
            $table->unsignedBigInteger('prompt_tokens')->default(0);
            $table->unsignedBigInteger('completion_tokens')->default(0);
            $table->unsignedBigInteger('total_tokens')->default(0);
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'created_at']);
            $table->index(['provider', 'model']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::connection('ai_mysql')->dropIfExists('ai_request_logs');
    }
};
