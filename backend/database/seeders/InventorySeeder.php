<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\InventoryItem;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            // Gallon Caps
            [
                'item_name' => 'Standard Blue Gallon Cap',
                'category' => 'caps',
                'quantity' => 150,
                'unit' => 'pcs',
                'unit_price' => 5.00,
                'reorder_level' => 50,
                'description' => 'Standard blue plastic caps for 5-gallon water bottles',
            ],
            [
                'item_name' => 'Premium White Gallon Cap',
                'category' => 'caps',
                'quantity' => 100,
                'unit' => 'pcs',
                'unit_price' => 7.50,
                'reorder_level' => 40,
                'description' => 'Premium quality white caps with enhanced seal',
            ],
            [
                'item_name' => 'Replacement Cap with Handle',
                'category' => 'caps',
                'quantity' => 75,
                'unit' => 'pcs',
                'unit_price' => 10.00,
                'reorder_level' => 30,
                'description' => 'Caps with built-in handle for easy carrying',
            ],

            // Seals
            [
                'item_name' => 'Tamper-Proof Security Seal',
                'category' => 'seals',
                'quantity' => 500,
                'unit' => 'pcs',
                'unit_price' => 2.00,
                'reorder_level' => 100,
                'description' => 'Disposable tamper-proof seals for quality assurance',
            ],
            [
                'item_name' => 'Heat Shrink Seal Bands',
                'category' => 'seals',
                'quantity' => 300,
                'unit' => 'pcs',
                'unit_price' => 3.50,
                'reorder_level' => 80,
                'description' => 'Heat shrink bands for cap sealing',
            ],
            [
                'item_name' => 'Water Quality Seal Stickers',
                'category' => 'seals',
                'quantity' => 250,
                'unit' => 'pcs',
                'unit_price' => 1.50,
                'reorder_level' => 100,
                'description' => 'Quality assurance stickers with date printing',
            ],

            // Purification Materials
            [
                'item_name' => 'Activated Carbon Filter',
                'category' => 'purification',
                'quantity' => 20,
                'unit' => 'pcs',
                'unit_price' => 450.00,
                'reorder_level' => 5,
                'description' => 'High-quality activated carbon filters for water purification',
            ],
            [
                'item_name' => 'Sediment Filter Cartridge',
                'category' => 'purification',
                'quantity' => 25,
                'unit' => 'pcs',
                'unit_price' => 250.00,
                'reorder_level' => 8,
                'description' => '5-micron sediment filter cartridges',
            ],
            [
                'item_name' => 'UV Lamp Replacement',
                'category' => 'purification',
                'quantity' => 8,
                'unit' => 'pcs',
                'unit_price' => 850.00,
                'reorder_level' => 3,
                'description' => 'UV sterilization lamp for water treatment',
            ],
            [
                'item_name' => 'Reverse Osmosis Membrane',
                'category' => 'purification',
                'quantity' => 6,
                'unit' => 'pcs',
                'unit_price' => 1200.00,
                'reorder_level' => 2,
                'description' => 'RO membrane for advanced water filtration',
            ],
            [
                'item_name' => 'Chlorine Tablets',
                'category' => 'purification',
                'quantity' => 12,
                'unit' => 'kg',
                'unit_price' => 180.00,
                'reorder_level' => 5,
                'description' => 'Water disinfection chlorine tablets',
            ],

            // Other Supplies
            [
                'item_name' => 'Cleaning Brushes (Long)',
                'category' => 'supplies',
                'quantity' => 30,
                'unit' => 'pcs',
                'unit_price' => 45.00,
                'reorder_level' => 10,
                'description' => 'Long-handled brushes for gallon interior cleaning',
            ],
            [
                'item_name' => 'Sanitizing Solution',
                'category' => 'supplies',
                'quantity' => 15,
                'unit' => 'liters',
                'unit_price' => 120.00,
                'reorder_level' => 5,
                'description' => 'Food-grade sanitizing solution for bottle cleaning',
            ],
            [
                'item_name' => 'Delivery Labels',
                'category' => 'supplies',
                'quantity' => 1000,
                'unit' => 'pcs',
                'unit_price' => 0.50,
                'reorder_level' => 200,
                'description' => 'Customer delivery tracking labels',
            ],
            [
                'item_name' => 'Plastic Gloves (Box)',
                'category' => 'supplies',
                'quantity' => 20,
                'unit' => 'box',
                'unit_price' => 85.00,
                'reorder_level' => 5,
                'description' => 'Disposable plastic gloves for handling, 100pcs per box',
            ],
            [
                'item_name' => 'Gallon Storage Racks',
                'category' => 'supplies',
                'quantity' => 8,
                'unit' => 'pcs',
                'unit_price' => 650.00,
                'reorder_level' => 2,
                'description' => 'Metal storage racks for organizing gallons',
            ],
            [
                'item_name' => 'Delivery Crates',
                'category' => 'supplies',
                'quantity' => 25,
                'unit' => 'pcs',
                'unit_price' => 200.00,
                'reorder_level' => 8,
                'description' => 'Plastic crates for safe gallon transportation',
            ],
            [
                'item_name' => 'Water Quality Test Kit',
                'category' => 'supplies',
                'quantity' => 5,
                'unit' => 'kit',
                'unit_price' => 450.00,
                'reorder_level' => 2,
                'description' => 'Complete water quality testing kit',
            ],
            [
                'item_name' => 'Repair Tape (Industrial)',
                'category' => 'supplies',
                'quantity' => 12,
                'unit' => 'roll',
                'unit_price' => 35.00,
                'reorder_level' => 5,
                'description' => 'Heavy-duty tape for equipment repairs',
            ],
        ];

        foreach ($items as $item) {
            InventoryItem::create($item);
        }
    }
}
