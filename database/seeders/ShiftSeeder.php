<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Shift;

class ShiftSeeder extends Seeder
{
    public function run(): void
    {
        Shift::create([
            'name' => 'Pagi',
            'start_time' => '08:00:00',
            'end_time' => '14:00:00',
            'color' => '#FACC15',
        ]);

        Shift::create([
            'name' => 'Malam',
            'start_time' => '14:00:00',
            'end_time' => '22:00:00',
            'color' => '#2563EB',
        ]);

        Shift::create([
            'name' => 'OFF',
            'start_time' => '00:00:00',
            'end_time' => '00:00:00',
            'color' => '#9CA3AF',
        ]);
    }
}
