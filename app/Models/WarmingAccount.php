<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WarmingAccount extends Model
{
    use HasFactory;


    protected $fillable = [
        'email', 'display_name', 'domain', 'session_dir',
        'is_logged_in', 'daily_limit', 'current_day_sent',
        'total_sent', 'warming_day', 'warming_started_at',
        'status', 'last_sent_at', 'last_login_check', 'notes',
    ];

    protected $casts = [
        'is_logged_in' => 'boolean',
        'daily_limit' => 'integer',
        'current_day_sent' => 'integer',
        'total_sent' => 'integer',
        'warming_day' => 'integer',
        'warming_started_at' => 'date',
        'last_sent_at' => 'datetime',
        'last_login_check' => 'datetime',
    ];

    // --- Relationships ---

    public function logs()
    {
        return $this->hasMany(WarmingLog::class);
    }

    // --- Scopes ---

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeReady($query)
    {
        return $query->where('status', 'active')->where('is_logged_in', true);
    }

    // --- Business Logic ---

    /**
     * Check if this account can still send emails today.
     */
    public function canSendToday(): bool
    {
        return $this->status === 'active'
            && $this->is_logged_in
            && $this->current_day_sent < $this->daily_limit;
    }

    /**
     * Get remaining sends for today.
     */
    public function getRemainingTodayAttribute(): int
    {
        return max(0, $this->daily_limit - $this->current_day_sent);
    }

    /**
     * Increment the sent counter after a successful send.
     */
    public function incrementSent(): void
    {
        $this->increment('current_day_sent');
        $this->increment('total_sent');
        $this->update(['last_sent_at' => now()]);
    }

    /**
     * Reset the daily counter (called via scheduled command each day).
     */
    public function resetDailyCount(): void
    {
        $this->update([
            'current_day_sent' => 0,
            'warming_day' => $this->warming_day + 1,
        ]);
    }

    /**
     * Get the browser session directory path.
     */
    public function getSessionPath(): string
    {
        if ($this->session_dir) {
            return $this->session_dir;
        }

        $dir = storage_path('app/warming_sessions/' . $this->id);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $this->update(['session_dir' => $dir]);

        return $dir;
    }

    /**
     * Get status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'active' => 'emerald',
            'paused' => 'amber',
            'pending' => 'zinc',
            'suspended' => 'red',
            default => 'zinc',
        };
    }

    /**
     * Get recent warming history (contribution graph data).
     */
    public function getRecentHistory(int $days = 30): array
    {
        $startDate = now()->subDays($days - 1)->startOfDay();
        
        $logs = $this->logs()
            ->where('status', 'sent')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, count(*) as count')
            ->groupBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        $history = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $history[$date] = $logs[$date] ?? 0;
        }

        return $history;
    }
}
