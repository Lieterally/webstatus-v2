<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('downtime_histories', function (Blueprint $table) {
            $table->enum('status', ['active', 'resolved', 'monitoring_stopped'])->default('active')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('downtime_histories')
            ->where('status', 'monitoring_stopped')
            ->update(['status' => 'resolved']);

        Schema::table('downtime_histories', function (Blueprint $table) {
            $table->enum('status', ['active', 'resolved'])->default('active')->change();
        });
    }
};
