<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemLog extends Model
{
    use HasFactory;

    protected $table = 'system_logs';

    protected $fillable = [
        'user_id',
        'user_name',
        'user_email',
        'user_role',
        'action',
        'details',
        'platform',
        'device',
        'ip_address',
        'user_agent',
    ];

    public $timestamps = false;

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeLogins($query)
    {
        return $query->where('action', 'login');
    }

    public function scopeLogouts($query)
    {
        return $query->where('action', 'logout');
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('user_role', $role);
    }

    public function scopeByPlatform($query, $platform)
    {
        return $query->where('platform', $platform);
    }

    // Helper method to create log entry
    public static function logActivity($user, $action, $request, $platform = 'web', $details = null)
    {
        return self::create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'user_role' => $user->role,
            'action' => $action,
            'details' => $details,
            'platform' => $platform,
            'device' => self::getDeviceInfo($request),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    private static function getDeviceInfo($request)
    {
        $agent = $request->userAgent();
        
        if (preg_match('/Mobile|Android|iPhone|iPad|iPod/', $agent)) {
            if (preg_match('/Android/', $agent)) {
                return 'Android Mobile';
            } elseif (preg_match('/iPhone/', $agent)) {
                return 'iPhone';
            } elseif (preg_match('/iPad/', $agent)) {
                return 'iPad';
            }
            return 'Mobile Device';
        }
        
        if (preg_match('/Windows/', $agent)) {
            return 'Windows PC';
        } elseif (preg_match('/Mac/', $agent)) {
            return 'Mac';
        } elseif (preg_match('/Linux/', $agent)) {
            return 'Linux PC';
        }
        
        return 'Unknown Device';
    }
}
