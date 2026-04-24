<?php

namespace Database\Seeders;

use App\Models\ScannerState;
use Illuminate\Database\Seeder;

class ScannerStateSeeder extends Seeder
{
    public function run(): void
    {
        ScannerState::updateOrCreate(
            ['id' => 1],
            [
                'active_mode' => 'idle',
                'target_item_id' => null,
            ]
        );
    }
}
