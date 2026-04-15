<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ai_mysql')->table('ai_conversations', function (Blueprint $table): void {
            $table->index(['user_id', 'last_used_at', 'id'], 'ai_conv_user_last_used_id_idx');
        });

        Schema::connection('ai_mysql')->table('ai_messages', function (Blueprint $table): void {
            $table->index(['ai_conversation_id', 'role', 'id'], 'ai_msg_conv_role_id_idx');
        });
    }

    public function down(): void
    {
        Schema::connection('ai_mysql')->table('ai_conversations', function (Blueprint $table): void {
            $table->dropIndex('ai_conv_user_last_used_id_idx');
        });

        Schema::connection('ai_mysql')->table('ai_messages', function (Blueprint $table): void {
            $table->dropIndex('ai_msg_conv_role_id_idx');
        });
    }
};
