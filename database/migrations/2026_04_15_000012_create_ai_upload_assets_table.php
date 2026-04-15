<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ai_mysql')->create('ai_upload_assets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('ai_conversation_id')->nullable();
            $table->string('attachment_type', 40)->default('file');
            $table->string('name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('storage_disk', 40)->default('local');
            $table->string('storage_path');
            $table->text('text_preview')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'ai_conversation_id']);
            $table->index(['tenant_id', 'attachment_type']);
        });
    }

    public function down(): void
    {
        Schema::connection('ai_mysql')->dropIfExists('ai_upload_assets');
    }
};
