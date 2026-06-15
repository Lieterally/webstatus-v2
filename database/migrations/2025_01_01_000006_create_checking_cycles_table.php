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
        Schema::create('checking_cycles', function (Blueprint $table) {
            $table->id();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->enum('trigger_type', ['automatic', 'manual_all', 'manual_site']);
            $table->integer('sites_checked')->default(0);
            $table->integer('sites_down')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checking_cycles');
    }
};
