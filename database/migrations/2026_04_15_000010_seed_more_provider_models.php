<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $rows = [
            ['provider' => 'openrouter', 'model_key' => 'openrouter/auto', 'display_name' => 'OpenRouter Auto', 'description' => 'OpenRouter auto-routing model.', 'max_output_tokens' => 8192, 'supports_streaming' => true, 'supports_vision' => true, 'is_default' => true],
            ['provider' => 'openrouter', 'model_key' => 'anthropic/claude-3.5-sonnet', 'display_name' => 'Claude 3.5 Sonnet (OpenRouter)', 'description' => 'Anthropic model served via OpenRouter.', 'max_output_tokens' => 8192, 'supports_streaming' => true, 'supports_vision' => true, 'is_default' => false],

            ['provider' => 'mistral', 'model_key' => 'mistral-large-latest', 'display_name' => 'Mistral Large', 'description' => 'Mistral flagship model.', 'max_output_tokens' => 8192, 'supports_streaming' => true, 'supports_vision' => false, 'is_default' => true],
            ['provider' => 'mistral', 'model_key' => 'mistral-small-latest', 'display_name' => 'Mistral Small', 'description' => 'Smaller low-latency Mistral model.', 'max_output_tokens' => 8192, 'supports_streaming' => true, 'supports_vision' => false, 'is_default' => false],

            ['provider' => 'cohere', 'model_key' => 'command-r-plus', 'display_name' => 'Cohere Command R+', 'description' => 'Cohere enterprise-grade model.', 'max_output_tokens' => 4096, 'supports_streaming' => true, 'supports_vision' => false, 'is_default' => true],
            ['provider' => 'cohere', 'model_key' => 'command-r', 'display_name' => 'Cohere Command R', 'description' => 'Cohere general-purpose model.', 'max_output_tokens' => 4096, 'supports_streaming' => true, 'supports_vision' => false, 'is_default' => false],

            ['provider' => 'perplexity', 'model_key' => 'sonar', 'display_name' => 'Perplexity Sonar', 'description' => 'Perplexity search-grounded model.', 'max_output_tokens' => 8192, 'supports_streaming' => true, 'supports_vision' => false, 'is_default' => true],
            ['provider' => 'perplexity', 'model_key' => 'sonar-reasoning', 'display_name' => 'Perplexity Sonar Reasoning', 'description' => 'Perplexity reasoning-optimized model.', 'max_output_tokens' => 8192, 'supports_streaming' => true, 'supports_vision' => false, 'is_default' => false],
        ];

        foreach ($rows as $row) {
            DB::connection('ai_mysql')->table('ai_models')->updateOrInsert(
                ['model_key' => $row['model_key']],
                array_merge($row, [
                    'is_active' => true,
                    'metadata' => json_encode(['family' => $row['provider']]),
                    'updated_at' => now(),
                    'created_at' => now(),
                ])
            );
        }
    }

    public function down(): void
    {
        DB::connection('ai_mysql')->table('ai_models')
            ->whereIn('model_key', [
                'openrouter/auto',
                'anthropic/claude-3.5-sonnet',
                'mistral-large-latest',
                'mistral-small-latest',
                'command-r-plus',
                'command-r',
                'sonar',
                'sonar-reasoning',
            ])->delete();
    }
};
