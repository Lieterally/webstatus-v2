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
        Schema::create('check_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->foreignId('cycle_id')->constrained('checking_cycles')->cascadeOnDelete();
            $table->integer('http_code')->default(0);
            $table->float('response_time_ms')->default(0);
            $table->enum('error_type', ['none', 'timeout', 'connection_failure', 'dns_failure'])->default('none');
            $table->timestamp('checked_at');

            $table->index('site_id');
            $table->index('page_id');
            $table->index('cycle_id');
            $table->index(['site_id', 'checked_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('check_results');
    }
};
