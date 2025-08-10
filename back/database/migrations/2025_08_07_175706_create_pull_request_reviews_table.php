<?php

// Migration: create_pull_request_files_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pull_request_files', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->enum('status', ['added', 'modified', 'removed', 'renamed'])->default('modified');
            $table->integer('additions')->default(0);
            $table->integer('deletions')->default(0);
            $table->integer('changes')->default(0);
            $table->string('blob_url')->nullable();
            $table->string('raw_url')->nullable();
            $table->text('patch')->nullable();
            $table->string('previous_filename')->nullable();
            $table->string('language', 50)->nullable();
            $table->foreignId('pull_request_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->index(['pull_request_id', 'language']);
            $table->index('filename');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pull_request_files');
    }
};
