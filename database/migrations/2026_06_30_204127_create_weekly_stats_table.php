<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->date('period_start'); // Monday of the week
            $table->float('avg_response_time_ms')->default(0);
            $table->integer('downtime_seconds')->default(0);
            $table->integer('checks_count')->default(0);
            $table->timestamps();

            $table->unique(['site_id', 'period_start']);
            $table->index('period_start');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_stats');
    }
};
