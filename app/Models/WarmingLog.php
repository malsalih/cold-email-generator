<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * WarmingLog — Tracks every warming email through its lifecycle.
 *
 * Status flow: pending → processing → sent | failed
 *
 * - `pending`:    Queued, waiting for bot to pick up
 * - `processing`: Bot picked up, currently being sent (locked)
 * - `sent`:       Successfully delivered
 * - `failed`:     Something went wrong (see error_message)
 *
 * Stale `processing` jobs (>10 min) are auto-reset to `pending` by the API.
 */
class WarmingLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'warming_account_id', 'warming_template_id', 'generated_email_id',
        'campaign_id', 'recipient_email', 'subject_sent', 'body_sent',
        'status', 'error_message', 'source_type', 'sent_at', 'scheduled_at',
        'is_followup', 'followup_number', 'schedule_send_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'schedule_send_at' => 'datetime',
        'is_followup' => 'boolean',
        'followup_number' => 'integer',
    ];

    // --- Relationships ---

    public function account()
    {
        return $this->belongsTo(WarmingAccount::class, 'warming_account_id');
    }

    public function template()
    {
        return $this->belongsTo(WarmingTemplate::class, 'warming_template_id');
    }

    public function generatedEmail()
    {
        return $this->belongsTo(GeneratedEmail::class, 'generated_email_id');
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    // --- Scopes ---

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('sent_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('sent_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeWarming($query)
    {
        return $query->where('source_type', 'warming');
    }

    public function scopeCampaign($query)
    {
        return $query->where('source_type', 'campaign');
    }

    /**
     * Only jobs that are ready to send now (scheduled_at is null or in the past).
     */
    public function scopeReadyToSend($query)
    {
        return $query->where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('scheduled_at')
                  ->orWhere('scheduled_at', '<=', now());
            });
    }

    /**
     * Stale processing jobs (stuck > 10 min).
     */
    public function scopeStale($query)
    {
        return $query->where('status', 'processing')
            ->where('updated_at', '<', now()->subMinutes(10));
    }

    /**
     * Get status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'sent' => 'emerald',
            'pending' => 'amber',
            'processing' => 'sky',
            'failed' => 'red',
            'bounced' => 'orange',
            default => 'zinc',
        };
    }
}
