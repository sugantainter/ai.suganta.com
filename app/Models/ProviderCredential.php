<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderCredential extends Model
{
    protected $table = 'ai_provider_credentials';

    protected $fillable = [
        'tenant_id',
        'provider',
        'encrypted_api_key',
        'meta',
        'is_active',
        'last_used_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'encrypted_api_key' => 'encrypted',
    ];

    public function getConnectionName(): ?string
    {
        return (string) config('ai.database_connection', 'ai_mysql');
    }
}
