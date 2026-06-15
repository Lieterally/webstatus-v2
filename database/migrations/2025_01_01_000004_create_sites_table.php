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
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('category_id')->constrained('categories');
            $table->string('base_url')->unique();
            $table->text('description')->nullable();
            $table->foreignId('responsible_person_id')->constrained('it_staffs');
            $table->enum('status', ['up', 'partially_down', 'totally_down'])->default('up');
            $table->integer('consecutive_down_count')->default(0);
            $table->timestamp('first_down_at')->nullable();
            $table->boolean('notification_sent')->default(false);
            $table->integer('notification_cycle_counter')->default(0);
            $table->float('avg_response_time')->default(0);
            $table->timestamps();

            $table->index('category_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
