<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $connection = DB::connection('ai_mysql')->table('ai_models');

        // Remove all existing Grok models before inserting the new catalog.
        $connection->where('provider', 'grok')->delete();

        $rows = [
            [
                'provider' => 'grok',
                'model_key' => 'grok-4.20-0309-reasoning',
                'display_name' => 'Grok 4.20 0309 Reasoning',
                'description' => 'Advanced Grok reasoning model.',
                'max_output_tokens' => 8192,
                'supports_streaming' => true,
                'supports_vision' => true,
                'supports_reasoning' => true,
                'supports_web_search' => false,
                'supports_tools' => true,
                'is_active' => true,
                'is_default' => true,
                'metadata' => json_encode(['family' => 'grok']),
            ],
            [
                'provider' => 'grok',
                'model_key' => 'grok-4.20-0309-non-reasoning',
                'display_name' => 'Grok 4.20 0309 Non-Reasoning',
                'description' => 'General purpose Grok 4.20 model.',
                'max_output_tokens' => 8192,
                'supports_streaming' => true,
                'supports_vision' => true,
                'supports_reasoning' => false,
                'supports_web_search' => false,
                'supports_tools' => true,
                'is_active' => true,
                'is_default' => false,
                'metadata' => json_encode(['family' => 'grok']),
            ],
            [
                'provider' => 'grok',
                'model_key' => 'grok-4.20-multi-agent-0309',
                'display_name' => 'Grok 4.20 Multi-Agent 0309',
                'description' => 'Multi-agent Grok model for advanced workflows.',
                'max_output_tokens' => 8192,
                'supports_streaming' => true,
                'supports_vision' => true,
                'supports_reasoning' => true,
                'supports_web_search' => false,
                'supports_tools' => true,
                'is_active' => true,
                'is_default' => false,
                'metadata' => json_encode(['family' => 'grok']),
            ],
            [
                'provider' => 'grok',
                'model_key' => 'grok-4.1-fast-reasoning',
                'display_name' => 'Grok 4.1 Fast Reasoning',
                'description' => 'Fast Grok model optimized for reasoning.',
                'max_output_tokens' => 8192,
                'supports_streaming' => true,
                'supports_vision' => true,
                'supports_reasoning' => true,
                'supports_web_search' => false,
                'supports_tools' => true,
                'is_active' => true,
                'is_default' => false,
                'metadata' => json_encode(['family' => 'grok']),
            ],
            [
                'provider' => 'grok',
                'model_key' => 'grok-4.1-fast-non-reasoning',
                'display_name' => 'Grok 4.1 Fast Non-Reasoning',
                'description' => 'Fast Grok model for low-latency general responses.',
                'max_output_tokens' => 8192,
                'supports_streaming' => true,
                'supports_vision' => true,
                'supports_reasoning' => false,
                'supports_web_search' => false,
                'supports_tools' => true,
                'is_active' => true,
                'is_default' => false,
                'metadata' => json_encode(['family' => 'grok']),
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
        $connection = DB::connection('ai_mysql')->table('ai_models');

        $connection->where('provider', 'grok')->delete();

        $restoreRows = [
            [
                'provider' => 'grok',
                'model_key' => 'grok-2-latest',
                'display_name' => 'Grok 2 Latest',
                'description' => 'xAI Grok flagship model.',
                'max_output_tokens' => 8192,
                'supports_streaming' => true,
                'supports_vision' => true,
                'supports_reasoning' => false,
                'supports_web_search' => false,
                'supports_tools' => false,
                'is_active' => true,
                'is_default' => true,
                'metadata' => json_encode(['family' => 'grok']),
            ],
            [
                'provider' => 'grok',
                'model_key' => 'grok-2-mini',
                'display_name' => 'Grok 2 Mini',
                'description' => 'Lower latency Grok model.',
                'max_output_tokens' => 8192,
                'supports_streaming' => true,
                'supports_vision' => true,
                'supports_reasoning' => false,
                'supports_web_search' => false,
                'supports_tools' => false,
                'is_active' => true,
                'is_default' => false,
                'metadata' => json_encode(['family' => 'grok']),
            ],
        ];

        foreach ($restoreRows as $row) {
            $connection->updateOrInsert(
                ['model_key' => $row['model_key']],
                array_merge($row, [
                    'updated_at' => now(),
                    'created_at' => now(),
                ])
            );
        }
    }
};
