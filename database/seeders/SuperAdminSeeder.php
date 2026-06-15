<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->updateOrInsert(
            ['username' => 'superadmin'],
            [
                'username' => 'superadmin',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
                'last_login_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
