<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiUploadAsset extends Model
{
    protected $table = 'ai_upload_assets';

    protected $fillable = [
        'tenant_id',
        'ai_conversation_id',
        'attachment_type',
        'name',
        'mime_type',
        'size_bytes',
        'storage_disk',
        'storage_path',
        'text_preview',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function getConnectionName(): ?string
    {
        return (string) config('ai.database_connection', 'ai_mysql');
    }
}
