<?php

namespace Database\Seeders;

use App\Models\Item;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        if (Item::count() > 0) {
            return;
        }

        $now = now();

        $samples = [
            [
                'title' => 'Blue Student ID Card',
                'status' => 'lost',
                'reported_at' => $now->copy()->subHours(2),
                'location' => 'Building A, Room 201',
                'contact_info' => 'Telegram: @rupp_student',
                'description' => 'RUPP logo lanyard, name on back.',
            ],
            [
                'title' => 'Black Umbrella',
                'status' => 'found',
                'reported_at' => $now->copy()->subDay(),
                'location' => 'Library entrance',
                'contact_info' => '012 345 678',
                'description' => 'Compact foldable umbrella.',
            ],
            [
                'title' => 'Wireless Mouse',
                'status' => 'found',
                'reported_at' => $now->copy()->subDays(3),
                'location' => 'Computer Lab 3',
                'contact_info' => 'admin@rupp.edu.kh',
                'description' => 'Logitech, no USB dongle in bag.',
            ],
        ];

        foreach ($samples as $sample) {
            Item::create($sample);
        }
    }
}
