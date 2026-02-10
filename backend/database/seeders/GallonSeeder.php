<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Gallon;

class GallonSeeder extends Seeder
{
    public function run(): void
    {
        // Create 50 sample gallons
        $gallons = [];
        
        for ($i = 1; $i <= 50; $i++) {
            $gallons[] = [
                'gallon_code' => sprintf('WR-GAL-%04d', $i),
                'status' => 'IN',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        DB::table('gallons')->insert($gallons);
        
        $this->command->info('Created 50 sample gallons (WR-GAL-0001 to WR-GAL-0050)');
    }
}
