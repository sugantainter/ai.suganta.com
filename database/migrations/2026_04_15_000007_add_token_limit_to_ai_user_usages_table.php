<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::connection('ai_mysql')->hasColumn('ai_user_usages', 'token_limit')) {
            Schema::connection('ai_mysql')->table('ai_user_usages', function (Blueprint $table): void {
                $table->unsignedBigInteger('token_limit')->default(10000)->after('total_tokens');
            });
        }
    }

    public function down(): void
    {
        if (Schema::connection('ai_mysql')->hasColumn('ai_user_usages', 'token_limit')) {
            Schema::connection('ai_mysql')->table('ai_user_usages', function (Blueprint $table): void {
                $table->dropColumn('token_limit');
            });
        }
    }
};
