<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Add auto_review_enabled field to repositories
        Schema::table('repositories', function (Blueprint $table) {
            $table->boolean('auto_review_enabled')->default(true)->after('webhook_enabled');
        });

        // Add github_token field to users (if not exists)
        if (!Schema::hasColumn('users', 'github_token')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('github_token', 500)->nullable()->after('remember_token');
            });
        }
    }

    public function down()
    {
        Schema::table('repositories', function (Blueprint $table) {
            $table->dropColumn('auto_review_enabled');
        });

        if (Schema::hasColumn('users', 'github_token')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('github_token');
            });
        }
    }
};
