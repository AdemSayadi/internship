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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('code_submission_id');
            $table->integer('overall_score')->nullable();
            $table->integer('complexity_score')->nullable();
            $table->integer('security_score')->nullable();
            $table->integer('maintainability_score')->nullable();
            $table->integer('bug_count')->nullable();
            $table->text('ai_summary')->nullable();
            $table->json('suggestions')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed']);
            $table->float('processing_time')->nullable();
            $table->timestamps();

            $table->index('code_submission_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
