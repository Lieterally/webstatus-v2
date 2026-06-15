<?php

namespace Database\Seeders;

use App\Models\ITStaff;
use Illuminate\Database\Seeder;

class ITStaffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $staffs = [
            [
                'name' => 'Danu',
                'position' => 'Web Dev',
            ],
            [
                'name' => 'Fadhil',
                'position' => 'Web Dev',
            ],
            [
                'name' => 'Hemy',
                'position' => 'Web Dev',
            ],
            [
                'name' => 'Iqbal',
                'position' => 'Web Dev',
            ],
            [
                'name' => 'Barry',
                'position' => 'Network Admin',
            ],
            [
                'name' => 'Yunus',
                'position' => 'Network Staff',
            ],
            [
                'name' => 'Satrio',
                'position' => 'Network Staff',
            ],
            [
                'name' => 'Alit',
                'position' => 'Web Dev',
            ],
            [
                'name' => 'Wahyu',
                'position' => 'Web Dev',
            ],
            [
                'name' => 'Yudha',
                'position' => 'Web Dev',
            ],
            [
                'name' => 'Rama',
                'position' => 'Mobile Dev',
            ],
            [
                'name' => 'Dwi',
                'position' => 'IT Staff',
            ],

        ];

        foreach ($staffs as $staff) {
            ITStaff::updateOrCreate(
                ['name' => $staff['name']],
                ['position' => $staff['position']]
            );
        }
    }
}
