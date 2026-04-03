<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class UserActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'activity',
        'notes',
        'ip_address',
        'user_agent',
        'location',
        'is_successful',
    ];

    protected $casts = [
        'is_successful' => 'boolean',
    ];

    public const ACTIONS = [
        'login' => 'Login',
        'logout' => 'Logout',
        'failed_login' => 'Login Gagal',
        'password_reset' => 'Reset Password',
        'password_change' => 'Ubah Password',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function log(
        string $action,
        string $description = '',
        array $requestData = []
    ): self {
        return self::create([
            'user_id' => Auth::id(),
            'activity' => $action,
            'notes' => $description,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'location' => $requestData['location'] ?? null,
            'is_successful' => $requestData['is_successful'] ?? true,
        ]);
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('activity', $action);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
    }

    public function getActionNameAttribute(): string
    {
        return self::ACTIONS[$this->activity] ?? $this->activity;
    }

    public function getActionAttribute(): ?string
    {
        return $this->activity;
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->notes;
    }

    public function getRequestDataAttribute(): ?array
    {
        return null;
    }

    public function getBrowserAttribute(): string
    {
        $userAgent = $this->user_agent ?? '';

        if (str_contains($userAgent, 'Firefox')) {
            return 'Firefox';
        }
        if (str_contains($userAgent, 'Chrome')) {
            return 'Chrome';
        }
        if (str_contains($userAgent, 'Safari')) {
            return 'Safari';
        }
        if (str_contains($userAgent, 'Edge')) {
            return 'Edge';
        }
        if (str_contains($userAgent, 'Opera')) {
            return 'Opera';
        }

        return 'Unknown';
    }

    public function getOsAttribute(): string
    {
        $userAgent = $this->user_agent ?? '';

        if (str_contains($userAgent, 'Windows')) {
            return 'Windows';
        }
        if (str_contains($userAgent, 'Mac')) {
            return 'MacOS';
        }
        if (str_contains($userAgent, 'Linux')) {
            return 'Linux';
        }
        if (str_contains($userAgent, 'Android')) {
            return 'Android';
        }
        if (str_contains($userAgent, 'iOS')) {
            return 'iOS';
        }

        return 'Unknown';
    }
}
