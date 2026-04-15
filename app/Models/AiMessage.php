<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiMessage extends Model
{
    protected $table = 'ai_messages';

    protected $fillable = [
        'ai_conversation_id',
        'user_id',
        'content',
        'role',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'raw_request',
        'raw_response',
    ];

    protected $casts = [
        'raw_request' => 'array',
        'raw_response' => 'array',
    ];

    public function getConnectionName(): ?string
    {
        return (string) config('ai.database_connection', 'ai_mysql');
    }
}
