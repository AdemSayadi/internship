<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('repositories', function (Blueprint $table) {
            $table->bigInteger('github_id')->nullable()->unique()->after('provider');
            $table->json('meta')->nullable()->after('github_id');
        });
    }

    public function down()
    {
        Schema::table('repositories', function (Blueprint $table) {
            $table->dropColumn(['github_id', 'meta']);
        });
    }
};
