<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pull_request_reviews', function (Blueprint $table) {
            $table->id();
            $table->enum('review_type', ['ai_auto', 'manual'])->default('ai_auto');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('summary')->nullable();
            $table->integer('score')->nullable();
            $table->longText('feedback')->nullable();
            $table->json('suggestions')->nullable();
            $table->json('security_issues')->nullable();
            $table->json('performance_issues')->nullable();
            $table->json('code_quality_issues')->nullable();
            $table->foreignId('pull_request_id')->constrained()->onDelete('cascade');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['pull_request_id', 'status']);
            $table->index(['review_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pull_request_reviews');
    }
};
