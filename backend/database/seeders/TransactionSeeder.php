<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Gallon;
use App\Models\User;
use Carbon\Carbon;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@waterrefilling.local')->first();
        $employee = User::where('email', 'employee@waterrefilling.local')->first();
        
        $transactionTypes = ['walk-in', 'delivery', 'refill-only'];
        $paymentMethods = ['cash', 'gcash', 'card', 'bank-transfer'];
        $unitPrice = 25.00;
        
        // Create 30 transactions over the past 30 days
        $gallons = Gallon::all();
        $gallonIndex = 0;
        
        for ($i = 0; $i < 30; $i++) {
            $date = Carbon::now()->subDays($i);
            $transactionsPerDay = rand(1, 3); // 1-3 transactions per day
            
            for ($j = 0; $j < $transactionsPerDay; $j++) {
                $quantity = rand(1, 5);
                $totalAmount = $quantity * $unitPrice;
                $transactionType = $transactionTypes[array_rand($transactionTypes)];
                $paymentMethod = $paymentMethods[array_rand($paymentMethods)];
                $employeeId = rand(0, 1) ? $admin->id : $employee->id;
                
                $transaction = Transaction::create([
                    'transaction_code' => 'TXN-' . $date->format('Ymd') . '-' . str_pad($j + 1, 4, '0', STR_PAD_LEFT),
                    'customer_name' => $this->generateCustomerName(),
                    'customer_phone' => $this->generatePhoneNumber(),
                    'customer_address' => $transactionType === 'delivery' ? $this->generateAddress() : null,
                    'transaction_type' => $transactionType,
                    'payment_method' => $paymentMethod,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_amount' => $totalAmount,
                    'employee_id' => $employeeId,
                    'notes' => rand(0, 10) > 7 ? 'Special instructions for delivery' : null,
                    'created_at' => $date->setTime(rand(8, 18), rand(0, 59)),
                    'updated_at' => $date,
                ]);
                
                // Add transaction items (borrow gallons)
                for ($k = 0; $k < $quantity; $k++) {
                    if ($gallonIndex < $gallons->count()) {
                        $gallon = $gallons[$gallonIndex];
                        
                        TransactionItem::create([
                            'transaction_id' => $transaction->id,
                            'gallon_id' => $gallon->id,
                            'action' => 'BORROW',
                        ]);
                        
                        // Update gallon status
                        if ($i < 7) { // Last 7 days - some still out
                            $gallon->update([
                                'status' => 'OUT',
                                'last_transaction_id' => $transaction->id,
                                'last_borrowed_date' => $date,
                            ]);
                        } else { // Older transactions - returned
                            $gallon->update([
                                'status' => 'IN',
                                'last_transaction_id' => $transaction->id,
                                'last_returned_date' => $date->copy()->addDays(rand(1, 3)),
                            ]);
                        }
                        
                        $gallonIndex++;
                    }
                }
            }
        }
        
        $this->command->info('Created sample transactions with varying dates, types, and payment methods');
    }
    
    private function generateCustomerName()
    {
        $firstNames = ['Juan', 'Maria', 'Jose', 'Ana', 'Pedro', 'Rosa', 'Carlos', 'Sofia', 'Miguel', 'Elena'];
        $lastNames = ['Santos', 'Reyes', 'Cruz', 'Bautista', 'Garcia', 'Mendoza', 'Torres', 'Flores', 'Rivera', 'Ramos'];
        
        return $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
    }
    
    private function generatePhoneNumber()
    {
        return '09' . rand(100000000, 999999999);
    }
    
    private function generateAddress()
    {
        $streets = ['Main St', 'Rizal Ave', 'Bonifacio St', 'Mabini St', 'Luna St'];
        $barangays = ['Barangay 1', 'Barangay 2', 'Poblacion', 'San Jose', 'San Pedro'];
        
        return rand(1, 999) . ' ' . $streets[array_rand($streets)] . ', ' . $barangays[array_rand($barangays)];
    }
}
