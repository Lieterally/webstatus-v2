<?php

namespace Database\Seeders;

use App\Models\TelegramTarget;
use Illuminate\Database\Seeder;

class TelegramTargetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $targets = [
            [
                'chat_id' => '981874873',
                'username' => 'Alit',
                'is_active' => 1,
            ],
            [
                'chat_id' => '928600710',
                'username' => 'Fadil',
                'is_active' => 1,
            ],


        ];

        foreach ($targets as $t) {
            TelegramTarget::updateOrCreate(
                [
                    'chat_id' => $t['chat_id']
                ],
                [
                    'username' => $t['username'],
                    'is_active' => $t['is_active']
                ]
            );
        }
    }
}
