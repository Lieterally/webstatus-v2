<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $configs = [
            ['key' => 'cycle_interval_minutes', 'value' => '10', 'updated_at' => now()],
            ['key' => 'notification_cycle_threshold', 'value' => '6', 'updated_at' => now()],
            ['key' => 'false_positive_threshold', 'value' => '3', 'updated_at' => now()],
            ['key' => 'session_timeout_minutes', 'value' => '30', 'updated_at' => now()],
            ['key' => 'connection_timeout_seconds', 'value' => '10', 'updated_at' => now()],
            ['key' => 'response_timeout_seconds', 'value' => '25', 'updated_at' => now()],
            ['key' => 'concurrency_limit', 'value' => '30', 'updated_at' => now()],
        ];

        foreach ($configs as $config) {
            DB::table('system_configs')->updateOrInsert(
                ['key' => $config['key']],
                $config
            );
        }
    }
}
