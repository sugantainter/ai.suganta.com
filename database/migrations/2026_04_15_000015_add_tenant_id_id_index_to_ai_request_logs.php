<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ai_mysql')->table('ai_request_logs', function (Blueprint $table): void {
            $table->index(['tenant_id', 'id'], 'ai_req_logs_tenant_id_id_idx');
        });
    }

    public function down(): void
    {
        Schema::connection('ai_mysql')->table('ai_request_logs', function (Blueprint $table): void {
            $table->dropIndex('ai_req_logs_tenant_id_id_idx');
        });
    }
};
