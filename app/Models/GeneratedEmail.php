<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GeneratedEmail extends Model
{
    use HasFactory;


    protected $fillable = [
        'owned_domain',
        'target_website',
        'target_domain',
        'target_emails',
        'user_instructions',
        'product_service',
        'tone',
        'system_prompt',
        'full_prompt_sent',
        'generated_variants',
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
        'generated_variants' => 'array',
        'tokens_used' => 'integer',
        'generation_time_ms' => 'float',
    ];

    /**
     * Scope to filter by domain.
     */
    public function scopeForDomain($query, string $domain)
    {
        return $query->where('target_domain', $domain)
                     ->orWhere('owned_domain', $domain)
                     ->orWhere('target_website', $domain);
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
        if (!empty($this->generated_variants) && is_array($this->generated_variants)) {
            $firstVariant = $this->generated_variants[0];
            return \Illuminate\Support\Str::limit($firstVariant['body'] ?? '', 120);
        }
        return \Illuminate\Support\Str::limit($this->generated_body, 120);
    }
}
