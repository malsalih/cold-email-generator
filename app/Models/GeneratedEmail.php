<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GeneratedEmail extends Model
{
    use HasFactory;

    protected $fillable = [
        'target_domain',
        'target_emails',
        'user_instructions',
        'product_service',
        'tone',
        'system_prompt',
        'full_prompt_sent',
        'generated_subject',
        'generated_body',
        'gemini_model',
        'tokens_used',
        'generation_time_ms',
        'status',
        'notes',
    ];

    protected $casts = [
        'target_emails' => 'array',
        'tokens_used' => 'integer',
        'generation_time_ms' => 'float',
    ];

    /**
     * Scope to filter by domain.
     */
    public function scopeForDomain($query, string $domain)
    {
        return $query->where('target_domain', $domain);
    }

    /**
     * Scope to get latest first.
     */
    public function scopeLatestFirst($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Get a formatted preview of the email body.
     */
    public function getBodyPreviewAttribute(): string
    {
        return \Illuminate\Support\Str::limit($this->generated_body, 120);
    }
}
