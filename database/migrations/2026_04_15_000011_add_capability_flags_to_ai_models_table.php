<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ai_mysql')->table('ai_models', function (Blueprint $table): void {
            if (! Schema::connection('ai_mysql')->hasColumn('ai_models', 'supports_reasoning')) {
                $table->boolean('supports_reasoning')->default(false)->after('supports_vision');
            }
            if (! Schema::connection('ai_mysql')->hasColumn('ai_models', 'supports_web_search')) {
                $table->boolean('supports_web_search')->default(false)->after('supports_reasoning');
            }
            if (! Schema::connection('ai_mysql')->hasColumn('ai_models', 'supports_tools')) {
                $table->boolean('supports_tools')->default(false)->after('supports_web_search');
            }
        });

        DB::connection('ai_mysql')->table('ai_models')->update([
            'supports_tools' => true,
        ]);

        DB::connection('ai_mysql')->table('ai_models')
            ->whereIn('model_key', ['deepseek-reasoner', 'claude-3-7-sonnet-latest', 'sonar-reasoning'])
            ->update(['supports_reasoning' => true]);

        DB::connection('ai_mysql')->table('ai_models')
            ->whereIn('provider', ['perplexity'])
            ->update(['supports_web_search' => true]);

        DB::connection('ai_mysql')->table('ai_models')
            ->whereIn('model_key', ['gemini-2.5-pro', 'gemini-2.5-flash', 'openrouter/auto'])
            ->update(['supports_web_search' => true]);
    }

    public function down(): void
    {
        Schema::connection('ai_mysql')->table('ai_models', function (Blueprint $table): void {
            if (Schema::connection('ai_mysql')->hasColumn('ai_models', 'supports_tools')) {
                $table->dropColumn('supports_tools');
            }
            if (Schema::connection('ai_mysql')->hasColumn('ai_models', 'supports_web_search')) {
                $table->dropColumn('supports_web_search');
            }
            if (Schema::connection('ai_mysql')->hasColumn('ai_models', 'supports_reasoning')) {
                $table->dropColumn('supports_reasoning');
            }
        });
    }
};
