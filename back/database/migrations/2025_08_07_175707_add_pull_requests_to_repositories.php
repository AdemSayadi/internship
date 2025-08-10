<?php

// Migration: add_pull_requests_to_repositories.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repositories', function (Blueprint $table) {
            $table->string('webhook_id')->nullable()->after('is_private');
            $table->boolean('webhook_enabled')->default(false)->after('webhook_id');
            $table->timestamp('webhook_created_at')->nullable()->after('webhook_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('repositories', function (Blueprint $table) {
            $table->dropColumn(['webhook_id', 'webhook_enabled', 'webhook_created_at']);
        });
    }
};
