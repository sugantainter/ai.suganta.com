<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ai_mysql')->table('ai_conversations', function (Blueprint $table): void {
            $table->boolean('is_share_enabled')->default(false)->after('settings');
            $table->string('share_token', 80)->nullable()->after('is_share_enabled');
            $table->timestamp('share_expires_at')->nullable()->after('share_token');

            $table->unique('share_token');
            $table->index('is_share_enabled');
            $table->index('share_expires_at');
        });
    }

    public function down(): void
    {
        Schema::connection('ai_mysql')->table('ai_conversations', function (Blueprint $table): void {
            $table->dropIndex(['is_share_enabled']);
            $table->dropIndex(['share_expires_at']);
            $table->dropUnique(['share_token']);
            $table->dropColumn(['is_share_enabled', 'share_token', 'share_expires_at']);
        });
    }
};
