<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GallonLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'gallon_id',
        'transaction_id',
        'action',
        'old_status',
        'new_status',
        'performed_by',
        'notes',
    ];

    public $timestamps = false;

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // Relationships
    public function gallon()
    {
        return $this->belongsTo(Gallon::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($log) {
            $log->created_at = now();
        });
    }
}
