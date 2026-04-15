<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ai_mysql')->create('ai_models', function (Blueprint $table): void {
            $table->id();
            $table->string('provider');
            $table->string('model_key')->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->unsignedInteger('max_output_tokens')->nullable();
            $table->boolean('supports_streaming')->default(true);
            $table->boolean('supports_vision')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['provider', 'is_active']);
        });

        DB::connection('ai_mysql')->table('ai_models')->insert([
            [
                'provider' => 'openai',
                'model_key' => 'gpt-4o-mini',
                'display_name' => 'GPT-4o Mini',
                'description' => 'Fast and cost-effective general model.',
                'max_output_tokens' => 16384,
                'supports_streaming' => true,
                'supports_vision' => true,
                'is_active' => true,
                'is_default' => true,
                'metadata' => json_encode(['family' => 'gpt-4o']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'provider' => 'gemini',
                'model_key' => 'gemini-2.5-flash-lite',
                'display_name' => 'Gemini 2.5 Flash Lite',
                'description' => 'Fast low-latency Gemini model.',
                'max_output_tokens' => 8192,
                'supports_streaming' => true,
                'supports_vision' => true,
                'is_active' => true,
                'is_default' => true,
                'metadata' => json_encode(['family' => 'gemini-2.5']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'provider' => 'anthropic',
                'model_key' => 'claude-3-5-sonnet-latest',
                'display_name' => 'Claude 3.5 Sonnet',
                'description' => 'Balanced reasoning and quality model.',
                'max_output_tokens' => 8192,
                'supports_streaming' => true,
                'supports_vision' => true,
                'is_active' => true,
                'is_default' => true,
                'metadata' => json_encode(['family' => 'claude-3.5']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::connection('ai_mysql')->dropIfExists('ai_models');
    }
};
