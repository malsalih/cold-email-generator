<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'log_id', 'event', 'message', 'metadata', 'session_id', 'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function getEventIconAttribute(): string
    {
        return match ($this->event) {
            'started' => '🚀',
            'job_picked' => '📋',
            'composing' => '✏️',
            'fields_filled' => '✅',
            'waiting_user' => '✋',
            'sent' => '📨',
            'failed' => '❌',
            'idle' => '💤',
            'stopped' => '🛑',
            'error' => '⚠️',
            'verified' => '🔍',
            default => '📝',
        };
    }

    public function getEventColorAttribute(): string
    {
        return match ($this->event) {
            'started' => 'cyan',
            'sent', 'fields_filled', 'verified' => 'emerald',
            'failed', 'error', 'stopped' => 'red',
            'waiting_user' => 'amber',
            'composing', 'job_picked' => 'violet',
            default => 'zinc',
        };
    }

    public static function getLatestStatus(): array
    {
        $latest = static::orderBy('created_at', 'desc')->first();
        $sessionId = $latest?->session_id;

        $sessionLogs = $sessionId
            ? static::where('session_id', $sessionId)->orderBy('created_at', 'desc')->limit(50)->get()
            : collect();

        $sentCount = $sessionId
            ? static::where('session_id', $sessionId)->where('event', 'sent')->count()
            : 0;

        $failedCount = $sessionId
            ? static::where('session_id', $sessionId)->where('event', 'failed')->count()
            : 0;

        $isOnline = $latest && $latest->created_at->diffInMinutes(now()) < 5
            && !in_array($latest->event, ['stopped', 'error']);

        return [
            'is_online' => $isOnline,
            'current_event' => $latest?->event ?? 'offline',
            'current_message' => $latest?->message ?? 'البوت غير متصل',
            'last_activity' => $latest?->created_at,
            'session_sent' => $sentCount,
            'session_failed' => $failedCount,
            'session_logs' => $sessionLogs,
        ];
    }
}
