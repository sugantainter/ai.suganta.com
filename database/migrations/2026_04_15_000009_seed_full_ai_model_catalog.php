<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $rows = [
            ['provider' => 'openai', 'model_key' => 'gpt-4.1', 'display_name' => 'GPT-4.1', 'max_output_tokens' => 16384, 'supports_streaming' => true, 'supports_vision' => true, 'is_default' => false, 'description' => 'High intelligence flagship model.'],
            ['provider' => 'openai', 'model_key' => 'gpt-4.1-mini', 'display_name' => 'GPT-4.1 Mini', 'max_output_tokens' => 16384, 'supports_streaming' => true, 'supports_vision' => true, 'is_default' => false, 'description' => 'Balanced speed and quality model.'],
            ['provider' => 'openai', 'model_key' => 'gpt-4o', 'display_name' => 'GPT-4o', 'max_output_tokens' => 16384, 'supports_streaming' => true, 'supports_vision' => true, 'is_default' => false, 'description' => 'Omni multimodal model.'],
            ['provider' => 'openai', 'model_key' => 'gpt-4o-mini', 'display_name' => 'GPT-4o Mini', 'max_output_tokens' => 16384, 'supports_streaming' => true, 'supports_vision' => true, 'is_default' => true, 'description' => 'Fast and cost-effective general model.'],

            ['provider' => 'gemini', 'model_key' => 'gemini-2.5-pro', 'display_name' => 'Gemini 2.5 Pro', 'max_output_tokens' => 8192, 'supports_streaming' => true, 'supports_vision' => true, 'is_default' => false, 'description' => 'Highest quality Gemini model.'],
            ['provider' => 'gemini', 'model_key' => 'gemini-2.5-flash', 'display_name' => 'Gemini 2.5 Flash', 'max_output_tokens' => 8192, 'supports_streaming' => true, 'supports_vision' => true, 'is_default' => false, 'description' => 'Low latency Gemini model.'],
            ['provider' => 'gemini', 'model_key' => 'gemini-2.5-flash-lite', 'display_name' => 'Gemini 2.5 Flash Lite', 'max_output_tokens' => 8192, 'supports_streaming' => true, 'supports_vision' => true, 'is_default' => true, 'description' => 'Fast low-cost Gemini model.'],

            ['provider' => 'anthropic', 'model_key' => 'claude-3-7-sonnet-latest', 'display_name' => 'Claude 3.7 Sonnet', 'max_output_tokens' => 8192, 'supports_streaming' => true, 'supports_vision' => true, 'is_default' => false, 'description' => 'Advanced Claude Sonnet model.'],
            ['provider' => 'anthropic', 'model_key' => 'claude-3-5-sonnet-latest', 'display_name' => 'Claude 3.5 Sonnet', 'max_output_tokens' => 8192, 'supports_streaming' => true, 'supports_vision' => true, 'is_default' => true, 'description' => 'Balanced reasoning and quality model.'],
            ['provider' => 'anthropic', 'model_key' => 'claude-3-5-haiku-latest', 'display_name' => 'Claude 3.5 Haiku', 'max_output_tokens' => 8192, 'supports_streaming' => true, 'supports_vision' => true, 'is_default' => false, 'description' => 'Fast and lightweight Claude model.'],

            ['provider' => 'grok', 'model_key' => 'grok-2-latest', 'display_name' => 'Grok 2 Latest', 'max_output_tokens' => 8192, 'supports_streaming' => true, 'supports_vision' => true, 'is_default' => true, 'description' => 'xAI Grok flagship model.'],
            ['provider' => 'grok', 'model_key' => 'grok-2-mini', 'display_name' => 'Grok 2 Mini', 'max_output_tokens' => 8192, 'supports_streaming' => true, 'supports_vision' => true, 'is_default' => false, 'description' => 'Lower latency Grok model.'],

            ['provider' => 'deepseek', 'model_key' => 'deepseek-chat', 'display_name' => 'DeepSeek Chat', 'max_output_tokens' => 8192, 'supports_streaming' => true, 'supports_vision' => false, 'is_default' => true, 'description' => 'General chat and coding model.'],
            ['provider' => 'deepseek', 'model_key' => 'deepseek-reasoner', 'display_name' => 'DeepSeek Reasoner', 'max_output_tokens' => 8192, 'supports_streaming' => true, 'supports_vision' => false, 'is_default' => false, 'description' => 'Reasoning optimized model.'],
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
        DB::connection('ai_mysql')->table('ai_models')->whereIn('model_key', [
            'gpt-4.1',
            'gpt-4.1-mini',
            'gpt-4o',
            'gpt-4o-mini',
            'gemini-2.5-pro',
            'gemini-2.5-flash',
            'gemini-2.5-flash-lite',
            'claude-3-7-sonnet-latest',
            'claude-3-5-sonnet-latest',
            'claude-3-5-haiku-latest',
            'grok-2-latest',
            'grok-2-mini',
            'deepseek-chat',
            'deepseek-reasoner',
        ])->delete();
    }
};
