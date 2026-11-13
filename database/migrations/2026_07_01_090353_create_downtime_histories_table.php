<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('downtime_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->integer('duration_seconds')->nullable(); // null while ongoing
            $table->json('affected_pages')->nullable(); // array of page paths
            $table->enum('status', ['active', 'resolved'])->default('active');
            $table->timestamps();

            $table->index(['site_id', 'status']);
            $table->index('started_at');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('downtime_histories');
    }
};
