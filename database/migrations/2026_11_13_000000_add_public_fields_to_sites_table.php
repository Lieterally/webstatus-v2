<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sites are hidden from the public status page until an admin marks them public.
     * public_name replaces the internal name on that page when set.
     */
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->boolean('is_public')->default(false)->after('is_monitored');
            $table->string('public_name', 100)->nullable()->after('is_public');
            $table->index('is_public');
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropIndex(['is_public']);
            $table->dropColumn(['is_public', 'public_name']);
        });
    }
};
