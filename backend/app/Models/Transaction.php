<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_code',
        'customer_name',
        'customer_phone',
        'customer_address',
        'transaction_type',
        'payment_method',
        'quantity',
        'unit_price',
        'total_amount',
        'employee_id',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function items()
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function gallons()
    {
        return $this->belongsToMany(Gallon::class, 'transaction_items')
            ->withPivot('action')
            ->withTimestamps();
    }

    // Scopes
    public function scopeWalkIn($query)
    {
        return $query->where('transaction_type', 'walk-in');
    }

    public function scopeDelivery($query)
    {
        return $query->where('transaction_type', 'delivery');
    }

    public function scopeRefillOnly($query)
    {
        return $query->where('transaction_type', 'refill-only');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
    }

    // Methods
    public static function generateTransactionCode()
    {
        $prefix = 'TXN-' . now()->format('Ymd') . '-';
        $lastTransaction = self::where('transaction_code', 'LIKE', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastTransaction) {
            $lastNumber = intval(substr($lastTransaction->transaction_code, -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return $prefix . $newNumber;
    }

    public function calculateTotal()
    {
        $this->setAttribute('total_amount', $this->quantity * $this->unit_price);
        $this->save();
    }
}
