<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ai_mysql')->create('ai_response_feedback', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('conversation_id')->default(0);
            $table->char('message_hash', 40);
            $table->string('feedback', 10);
            $table->string('provider', 80)->default('');
            $table->string('model', 120)->default('');
            $table->string('response_style', 20)->default('');
            $table->text('assistant_message');
            $table->timestamps();

            $table->unique(['tenant_id', 'conversation_id', 'message_hash'], 'ai_resp_feedback_unique');
            $table->index(['tenant_id', 'created_at'], 'ai_resp_feedback_tenant_created_idx');
            $table->index(['provider', 'model', 'feedback'], 'ai_resp_feedback_provider_model_feedback_idx');
        });
    }

    public function down(): void
    {
        Schema::connection('ai_mysql')->dropIfExists('ai_response_feedback');
    }
};
