<?php
// Create this migration: php artisan make:migration update_notifications_table_add_missing_fields

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('notifications', 'type')) {
                $table->string('type')->after('review_id')->nullable();
            }

            if (!Schema::hasColumn('notifications', 'title')) {
                $table->string('title')->after('type')->nullable();
            }

            if (!Schema::hasColumn('notifications', 'data')) {
                $table->json('data')->after('message')->nullable();
            }

            // Update existing notifications with default values
            // You can customize this based on your needs
        });

        // Update existing notifications to have proper structure
        DB::table('notifications')->whereNull('type')->update([
            'type' => 'legacy_notification',
            'title' => 'Legacy Notification'
        ]);
    }

    public function down()
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn(['type', 'title', 'data']);
        });
    }
};
