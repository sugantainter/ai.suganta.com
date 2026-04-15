<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestLog extends Model
{
    protected $table = 'ai_request_logs';

    protected $fillable = [
        'tenant_id',
        'api_key_id',
        'provider',
        'model',
        'status',
        'is_stream',
        'latency_ms',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'request_payload',
        'response_payload',
        'error_message',
    ];

    protected $casts = [
        'is_stream' => 'boolean',
        'request_payload' => 'array',
        'response_payload' => 'array',
    ];

    public function getConnectionName(): ?string
    {
        return (string) config('ai.database_connection', 'ai_mysql');
    }
}
