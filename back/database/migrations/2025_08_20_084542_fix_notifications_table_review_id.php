<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Make review_id nullable if it isn't already
            $table->unsignedBigInteger('review_id')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Revert the change
            $table->unsignedBigInteger('review_id')->nullable(false)->change();
        });
    }
};
