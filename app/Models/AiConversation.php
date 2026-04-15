<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiConversation extends Model
{
    protected $table = 'ai_conversations';

    protected $fillable = [
        'user_id',
        'subject',
        'status',
        'model',
        'purpose',
        'settings',
        'is_share_enabled',
        'share_token',
        'share_expires_at',
        'total_prompt_tokens',
        'total_completion_tokens',
        'total_tokens',
        'last_used_at',
        'last_error_code',
        'last_error_message',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_share_enabled' => 'boolean',
        'share_expires_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    public function getConnectionName(): ?string
    {
        return (string) config('ai.database_connection', 'ai_mysql');
    }
}
