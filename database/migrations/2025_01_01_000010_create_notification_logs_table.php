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
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->nullable()->constrained('sites')->cascadeOnDelete();
            $table->enum('type', ['down', 'recovery', 'status_change', 'system_health']);
            $table->text('message');
            $table->integer('targets_sent')->default(0);
            $table->integer('targets_failed')->default(0);
            $table->timestamp('sent_at');

            $table->index('site_id');
            $table->index(['site_id', 'sent_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
