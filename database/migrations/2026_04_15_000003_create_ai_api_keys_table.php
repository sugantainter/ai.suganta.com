<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ai_mysql')->create('ai_api_keys', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->string('key_hash', 64)->unique();
            $table->unsignedInteger('rate_limit_per_minute')->default(60);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::connection('ai_mysql')->dropIfExists('ai_api_keys');
    }
};
