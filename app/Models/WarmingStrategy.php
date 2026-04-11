<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WarmingStrategy extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'schedule',
        'min_delay_minutes', 'max_delay_minutes',
        'active_hours_start', 'active_hours_end',
        'is_default',
    ];

    protected $casts = [
        'schedule' => 'array',
        'min_delay_minutes' => 'integer',
        'max_delay_minutes' => 'integer',
        'is_default' => 'boolean',
    ];

    // --- Scopes ---

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    // --- Business Logic ---

    /**
     * Get the daily send limit for a given warming day.
     */
    public function getDailyLimitForDay(int $warmingDay): int
    {
        if (empty($this->schedule)) {
            return 2;
        }

        // Schedule format: [{from_day, to_day, daily_sends}, ...]
        foreach ($this->schedule as $tier) {
            $from = $tier['from_day'] ?? 0;
            $to = $tier['to_day'] ?? 999;
            if ($warmingDay >= $from && $warmingDay <= $to) {
                return $tier['daily_sends'] ?? 2;
            }
        }

        // If beyond all tiers, use the last tier's value
        $schedule = $this->schedule;
        $lastTier = end($schedule);
        return $lastTier['daily_sends'] ?? 25;
    }

    /**
     * Get a random delay in seconds between sends.
     */
    public function getRandomDelay(): int
    {
        $minSeconds = $this->min_delay_minutes * 60;
        $maxSeconds = $this->max_delay_minutes * 60;
        return rand($minSeconds, $maxSeconds);
    }

    /**
     * Check if we're within active sending hours.
     */
    public function isWithinActiveHours(): bool
    {
        $now = now()->format('H:i');
        return $now >= $this->active_hours_start && $now <= $this->active_hours_end;
    }

    /**
     * Get the default strategy, or create one if none exists.
     */
    public static function getDefault(): self
    {
        return self::where('is_default', true)->first() ?? self::createDefault();
    }

    /**
     * Create the default warming strategy.
     */
    public static function createDefault(): self
    {
        return self::create([
            'name' => 'Gradual Warm-Up (Default)',
            'description' => 'بداية بطيئة مع زيادة تدريجية على مدى 30 يوم للوصول إلى 25 إيميل يومياً',
            'schedule' => [
                ['from_day' => 1, 'to_day' => 3, 'daily_sends' => 2],
                ['from_day' => 4, 'to_day' => 7, 'daily_sends' => 5],
                ['from_day' => 8, 'to_day' => 14, 'daily_sends' => 10],
                ['from_day' => 15, 'to_day' => 21, 'daily_sends' => 15],
                ['from_day' => 22, 'to_day' => 30, 'daily_sends' => 20],
                ['from_day' => 31, 'to_day' => 999, 'daily_sends' => 25],
            ],
            'min_delay_minutes' => 3,
            'max_delay_minutes' => 15,
            'active_hours_start' => '08:00',
            'active_hours_end' => '20:00',
            'is_default' => true,
        ]);
    }
}
