<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $connection = DB::connection('ai_mysql')->table('ai_models');

        $rows = [
            [
                'provider' => 'grok',
                'model_key' => 'grok-2-latest',
                'display_name' => 'Grok 2 Latest',
                'description' => 'xAI Grok flagship model.',
                'max_output_tokens' => 8192,
                'supports_streaming' => true,
                'supports_vision' => true,
                'is_active' => true,
                'is_default' => true,
                'metadata' => json_encode(['family' => 'grok']),
            ],
            [
                'provider' => 'deepseek',
                'model_key' => 'deepseek-chat',
                'display_name' => 'DeepSeek Chat',
                'description' => 'General chat and coding model from DeepSeek.',
                'max_output_tokens' => 8192,
                'supports_streaming' => true,
                'supports_vision' => false,
                'is_active' => true,
                'is_default' => true,
                'metadata' => json_encode(['family' => 'deepseek']),
            ],
            [
                'provider' => 'deepseek',
                'model_key' => 'deepseek-reasoner',
                'display_name' => 'DeepSeek Reasoner',
                'description' => 'Reasoning-optimized DeepSeek model.',
                'max_output_tokens' => 8192,
                'supports_streaming' => true,
                'supports_vision' => false,
                'is_active' => true,
                'is_default' => false,
                'metadata' => json_encode(['family' => 'deepseek']),
            ],
        ];

        foreach ($rows as $row) {
            $connection->updateOrInsert(
                ['model_key' => $row['model_key']],
                array_merge($row, [
                    'updated_at' => now(),
                    'created_at' => now(),
                ])
            );
        }
    }

    public function down(): void
    {
        DB::connection('ai_mysql')->table('ai_models')
            ->whereIn('model_key', ['grok-2-latest', 'deepseek-chat', 'deepseek-reasoner'])
            ->delete();
    }
};
