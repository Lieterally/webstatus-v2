<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A public note per outage, written by staff from the public status page or the downtime page.
 * Note: `stats:aggregate --backfill` deletes and recreates outage rows, which drops these notes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('downtime_histories', function (Blueprint $table) {
            $table->text('note')->nullable()->after('status');
            $table->foreignId('note_updated_by')->nullable()->after('note')->constrained('users')->nullOnDelete();
            $table->timestamp('note_updated_at')->nullable()->after('note_updated_by');
        });
    }

    public function down(): void
    {
        Schema::table('downtime_histories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('note_updated_by');
            $table->dropColumn(['note', 'note_updated_at']);
        });
    }
};
