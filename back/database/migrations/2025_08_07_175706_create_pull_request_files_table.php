<?php

// Migration: create_pull_requests_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pull_requests', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body')->nullable();
            $table->bigInteger('github_pr_id')->unique();
            $table->integer('github_pr_number');
            $table->enum('state', ['open', 'closed', 'merged'])->default('open');
            $table->string('html_url');
            $table->string('head_sha');
            $table->string('base_sha');
            $table->string('head_branch');
            $table->string('base_branch');
            $table->string('author_username');
            $table->string('author_avatar_url')->nullable();
            $table->boolean('mergeable')->nullable();
            $table->timestamp('merged_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('repository_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->json('webhook_data')->nullable();
            $table->timestamps();

            $table->index(['repository_id', 'state']);
            $table->index(['user_id', 'created_at']);
            $table->index('github_pr_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pull_requests');
    }
};
