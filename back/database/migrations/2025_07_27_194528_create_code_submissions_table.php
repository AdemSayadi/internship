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
        Schema::create('code_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('language', 50)->nullable();
            $table->longText('code_content')->nullable();
            $table->string('file_path', 500)->nullable();
            $table->unsignedBigInteger('repository_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->index('repository_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('code_submissions');
    }
};
