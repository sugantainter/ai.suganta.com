<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model
{
    protected $table = 'ai_api_keys';

    protected $fillable = [
        'tenant_id',
        'name',
        'key_hash',
        'rate_limit_per_minute',
        'is_active',
        'last_used_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    public function getConnectionName(): ?string
    {
        return (string) config('ai.database_connection', 'ai_mysql');
    }
}
