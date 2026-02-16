<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gallon extends Model
{
    use HasFactory;

    protected $fillable = [
        'gallon_code',
        'status',
        'last_transaction_id',
        'last_borrowed_date',
        'last_returned_date',
        'is_overdue',
        'overdue_days',
    ];

    protected $casts = [
        'is_overdue' => 'boolean',
        'last_borrowed_date' => 'datetime',
        'last_returned_date' => 'datetime',
    ];

    // Relationships
    public function lastTransaction()
    {
        return $this->belongsTo(Transaction::class, 'last_transaction_id');
    }

    public function transactionItems()
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function logs()
    {
        return $this->hasMany(GallonLog::class)->orderBy('created_at', 'desc');
    }

    // Scopes
    public function scopeInStation($query)
    {
        return $query->where('status', 'IN');
    }

    public function scopeOut($query)
    {
        return $query->where('status', 'OUT');
    }

    public function scopeMissing($query)
    {
        return $query->where('status', 'MISSING');
    }

    public function scopeOverdue($query)
    {
        return $query->where('is_overdue', true);
    }

    // Methods
    public function markAsOut($transactionId)
    {
        $this->update([
            'status' => 'OUT',
            'last_transaction_id' => $transactionId,
            'last_borrowed_date' => now(),
            'is_overdue' => false,
            'overdue_days' => 0,
        ]);
    }

    public function markAsIn($transactionId = null)
    {
        $this->update([
            'status' => 'IN',
            'last_transaction_id' => $transactionId,
            'last_returned_date' => now(),
            'is_overdue' => false,
            'overdue_days' => 0,
        ]);
    }

    public function updateOverdueStatus()
    {
        if ($this->status === 'OUT' && $this->last_borrowed_date) {
            $daysBorrowed = now()->diffInDays($this->last_borrowed_date);
            $overdueThreshold = (int) SystemSetting::get('overdue_days_threshold', 7);
            $missingThreshold = (int) SystemSetting::get('missing_days_threshold', 30);

            $this->overdue_days = $daysBorrowed;
            $this->is_overdue = $daysBorrowed >= $overdueThreshold;

            if ($daysBorrowed >= $missingThreshold) {
                $this->status = 'MISSING';
            }

            $this->save();
        }
    }

    // Batch update overdue status for all OUT gallons
    public static function updateAllOverdueStatus()
    {
        $overdueThreshold = (int) SystemSetting::get('overdue_days_threshold', 7);
        $missingThreshold = (int) SystemSetting::get('missing_days_threshold', 30);
        
        // Update all OUT gallons
        $outGallons = self::where('status', 'OUT')
            ->whereNotNull('last_borrowed_date')
            ->get();
        
        foreach ($outGallons as $gallon) {
            $daysBorrowed = now()->diffInDays($gallon->last_borrowed_date);
            
            $gallon->overdue_days = $daysBorrowed;
            $gallon->is_overdue = $daysBorrowed >= $overdueThreshold;
            
            // Mark as MISSING if exceeds missing threshold
            if ($daysBorrowed >= $missingThreshold) {
                $gallon->status = 'MISSING';
            }
            
            $gallon->save();
        }
        
        // Reset overdue status for IN gallons
        self::where('status', 'IN')
            ->where(function($query) {
                $query->where('is_overdue', true)
                      ->orWhere('overdue_days', '>', 0);
            })
            ->update(['is_overdue' => false, 'overdue_days' => 0]);
        
        return $outGallons->count();
    }
}
