<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('repositories', function (Blueprint $table) {
            $table->bigInteger('github_repo_id')->nullable()->after('provider');
            $table->string('full_name')->nullable()->after('github_repo_id');
            $table->boolean('is_private')->default(false)->after('full_name');

            // Add index for better performance
            $table->index(['github_repo_id']);
        });
    }

    public function down()
    {
        Schema::table('repositories', function (Blueprint $table) {
            $table->dropColumn(['github_repo_id', 'full_name', 'is_private']);
            $table->dropIndex(['github_repo_id']);
        });
    }
};
