<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

/**
 * Campaign — Marketing email campaign with Send Later scheduling.
 *
 * Supports:
 * - Smart time distribution within business hours
 * - Multi-day spreading for large recipient lists
 * - Follow-up chain (parent → followup 1 → followup 2 → ...)
 * - Auto or manual follow-up selection
 * - Timezone-aware scheduling via Zoho Send Later
 *
 * Status flow: draft → scheduled/running → completed
 *              draft → scheduled/running → paused → running → completed
 */
class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'generated_email_id', 'warming_account_ids',
        'custom_subject', 'custom_body', 'email_variants',
        'recipients', 'delay_minutes', 'scheduled_at',
        'status', 'total_recipients', 'sent_count', 'failed_count',
        // Send Later scheduling
        'send_start_time', 'send_end_time',
        'min_delay_minutes', 'max_delay_minutes',
        'send_start_date', 'timezone',
        // Follow-up chain
        'parent_campaign_id', 'followup_number',
        'followup_wait_days', 'max_followups', 'auto_followup',
    ];

    protected $casts = [
        'warming_account_ids' => 'array',
        'recipients' => 'array',
        'email_variants' => 'array',
        'delay_minutes' => 'integer',
        'scheduled_at' => 'datetime',
        'total_recipients' => 'integer',
        'sent_count' => 'integer',
        'failed_count' => 'integer',
        'min_delay_minutes' => 'integer',
        'max_delay_minutes' => 'integer',
        'send_start_date' => 'date',
        'followup_number' => 'integer',
        'followup_wait_days' => 'integer',
        'max_followups' => 'integer',
        'auto_followup' => 'boolean',
    ];

    // ─── Relationships ───────────────────────────────

    public function generatedEmail()
    {
        return $this->belongsTo(GeneratedEmail::class);
    }

    public function getAccountsAttribute()
    {
        $ids = $this->warming_account_ids ?? [];
        return WarmingAccount::whereIn('id', $ids)->get();
    }

    public function logs()
    {
        return $this->hasMany(WarmingLog::class);
    }

    public function parentCampaign()
    {
        return $this->belongsTo(Campaign::class, 'parent_campaign_id');
    }

    public function followUps()
    {
        return $this->hasMany(Campaign::class, 'parent_campaign_id')->orderBy('followup_number');
    }

    // ─── Scopes ──────────────────────────────────────

    public function scopeLatestFirst($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['scheduled', 'running']);
    }

    public function scopeRootOnly($query)
    {
        return $query->where('followup_number', 0);
    }

    // ─── Accessors ───────────────────────────────────

    public function getPendingCountAttribute(): int
    {
        return max(0, $this->total_recipients - $this->sent_count - $this->failed_count);
    }

    public function getSentPercentAttribute(): int
    {
        if ($this->total_recipients === 0) return 0;
        return (int) round(($this->sent_count / $this->total_recipients) * 100);
    }

    public function getFailedPercentAttribute(): int
    {
        if ($this->total_recipients === 0) return 0;
        return (int) round(($this->failed_count / $this->total_recipients) * 100);
    }

    public function getProgressPercentAttribute(): int
    {
        if ($this->total_recipients === 0) return 0;
        return (int) round(($this->sent_count + $this->failed_count) / $this->total_recipients * 100);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'مسودة',
            'scheduled' => 'مُجدول',
            'running' => 'يعمل الآن',
            'paused' => 'متوقف مؤقتاً',
            'completed' => 'مكتمل',
            'failed' => 'فشل',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'zinc',
            'scheduled' => 'cyan',
            'running' => 'emerald',
            'paused' => 'amber',
            'completed' => 'green',
            'failed' => 'red',
            default => 'zinc',
        };
    }

    public function getIsFollowUpAttribute(): bool
    {
        return $this->followup_number > 0;
    }

    // ─── Business Logic ──────────────────────────────

    /**
     * Get the subject to use for this campaign.
     */
    public function getSubject(): string
    {
        if ($this->custom_subject) {
            return $this->custom_subject;
        }

        if ($this->generatedEmail) {
            $variants = $this->generatedEmail->generated_variants;
            if (!empty($variants[0]['subject'])) {
                return $variants[0]['subject'];
            }
            return $this->generatedEmail->generated_subject ?? '';
        }

        return '';
    }

    /**
     * Get the body to use for this campaign.
     */
    public function getBody(): string
    {
        if ($this->custom_body) {
            return $this->custom_body;
        }

        if ($this->generatedEmail) {
            $variants = $this->generatedEmail->generated_variants;
            if (!empty($variants[0]['body'])) {
                return $variants[0]['body'];
            }
            return $this->generatedEmail->generated_body ?? '';
        }

        return '';
    }

    /**
     * Create WarmingLog entries with smart business-hours scheduling.
     *
     * Distributes emails across business hours (send_start_time to send_end_time)
     * with random delays between min/max_delay_minutes. Spills into next days
     * if the recipient list is too large for one day.
     */
    public function createScheduledLogs(): void
    {
        $startDate = $this->send_start_date ? $this->send_start_date->format('Y-m-d') : now()->format('Y-m-d');
        $startTime = $this->send_start_time ?? '09:00';
        $endTime = $this->send_end_time ?? '17:00';
        $minDelay = $this->min_delay_minutes ?? 5;
        $maxDelay = $this->max_delay_minutes ?? 10;

        // Current cursor: first email send time
        $cursor = Carbon::parse("{$startDate} {$startTime}");
        $dayEndTime = Carbon::parse("{$startDate} {$endTime}");

        $accountIds = $this->warming_account_ids ?? [];
        if (empty($accountIds)) {
            \Illuminate\Support\Facades\Log::warning("Campaign {$this->id} has no sender accounts attached.");
        }
        $accountCount = count($accountIds) ?: 1;
        $accountIndex = 0;
        $totalCreated = 0;

        // Build the jobs list: either multi-variant or single template
        $jobs = [];

        if (!empty($this->email_variants) && is_array($this->email_variants)) {
            // Multi-template mode: each variant has its own recipients and content
            foreach ($this->email_variants as $variant) {
                $vSubject = $variant['subject'] ?? '';
                $vBody = $variant['body'] ?? '';
                $vRecipients = $variant['target_emails'] ?? $variant['recipients'] ?? [];
                foreach ($vRecipients as $email) {
                    $jobs[] = ['email' => trim($email), 'subject' => $vSubject, 'body' => $vBody];
                }
            }
        } else {
            // Single template mode: one subject/body for all recipients
            $subject = $this->getSubject();
            $body = $this->getBody();
            foreach ($this->recipients as $email) {
                $jobs[] = ['email' => trim($email), 'subject' => $subject, 'body' => $body];
            }
        }

        foreach ($jobs as $job) {
            // If cursor is past end of business hours, jump to next day
            if ($cursor->greaterThanOrEqualTo($dayEndTime)) {
                $cursor = $cursor->copy()->addDay()->setTimeFromTimeString($startTime);
                $dayEndTime = $cursor->copy()->setTimeFromTimeString($endTime);
            }

            WarmingLog::create([
                'warming_account_id' => $accountIds[$accountIndex] ?? null,
                'campaign_id' => $this->id,
                'generated_email_id' => $this->generated_email_id,
                'recipient_email' => $job['email'],
                'subject_sent' => $job['subject'],
                'body_sent' => $job['body'],
                'status' => 'pending',
                'scheduled_at' => $cursor->copy(),
                'schedule_send_at' => $cursor->copy(),
                'source_type' => 'campaign',
                'is_followup' => ($this->followup_number ?? 0) > 0,
                'followup_number' => $this->followup_number ?? 0,
            ]);

            $accountIndex = ($accountIndex + 1) % $accountCount;
            $totalCreated++;

            $delay = rand($minDelay, $maxDelay);
            $cursor->addMinutes($delay);
        }

        $this->update([
            'total_recipients' => $totalCreated,
            'status' => 'scheduled',
        ]);
    }

    /**
     * Get recipients who have been processed (sent or failed) to allow follow-up.
     * Returns all processed recipients — user selects who didn't reply.
     */
    public function getSentRecipients(): array
    {
        return $this->logs()
            ->whereIn('status', ['sent', 'failed'])
            ->pluck('recipient_email')
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Get recipients already followed up on.
     */
    public function getFollowedUpRecipients(): array
    {
        $followUpCampaignIds = $this->followUps()->pluck('id')->toArray();
        if (empty($followUpCampaignIds)) return [];

        return WarmingLog::whereIn('campaign_id', $followUpCampaignIds)
            ->pluck('recipient_email')
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Refresh sent/failed counts from logs.
     */
    public function refreshCounts(): void
    {
        $this->update([
            'sent_count' => $this->logs()->where('status', 'sent')->count(),
            'failed_count' => $this->logs()->where('status', 'failed')->count(),
        ]);

        // Auto-complete if all done
        $processed = $this->sent_count + $this->failed_count;
        if ($processed >= $this->total_recipients && $this->status === 'running') {
            $this->update(['status' => 'completed']);
        }
    }
}
