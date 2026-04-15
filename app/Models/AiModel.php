<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiModel extends Model
{
    protected $table = 'ai_models';

    protected $fillable = [
        'provider',
        'model_key',
        'display_name',
        'description',
        'max_output_tokens',
        'supports_streaming',
        'supports_vision',
        'is_active',
        'is_default',
        'metadata',
    ];

    protected $casts = [
        'supports_streaming' => 'boolean',
        'supports_vision' => 'boolean',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'metadata' => 'array',
    ];

    public function getConnectionName(): ?string
    {
        return (string) config('ai.database_connection', 'ai_mysql');
    }
}
