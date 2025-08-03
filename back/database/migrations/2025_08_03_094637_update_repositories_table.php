<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('repositories', function (Blueprint $table) {
            $table->bigInteger('github_repo_id')->nullable()->after('provider')->index();
            $table->text('description')->nullable()->after('github_repo_id');
            $table->boolean('is_private')->default(false)->after('description');
            $table->string('default_branch')->default('main')->after('is_private');

            // Add unique constraint to prevent duplicate GitHub repository imports
            $table->unique(['user_id', 'github_repo_id'], 'unique_user_github_repo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('repositories', function (Blueprint $table) {
            $table->dropUnique('unique_user_github_repo');
            $table->dropColumn([
                'github_repo_id',
                'description',
                'is_private',
                'default_branch'
            ]);
        });
    }
};
