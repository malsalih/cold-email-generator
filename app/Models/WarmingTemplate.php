<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class WarmingTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'category', 'subject', 'body',
        'variables', 'is_active', 'times_used',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
        'times_used' => 'integer',
    ];

    // --- Scopes ---

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    // --- Business Logic ---

    /**
     * Render the subject with variable replacements.
     */
    public function renderSubject(array $vars = []): string
    {
        return $this->replaceVariables($this->subject, $vars);
    }

    /**
     * Render the body with variable replacements.
     */
    public function renderBody(array $vars = []): string
    {
        return $this->replaceVariables($this->body, $vars);
    }

    /**
     * Replace {variable} placeholders with actual values.
     */
    protected function replaceVariables(string $text, array $vars): string
    {
        // Built-in variables
        $defaults = [
            '{date}' => now()->format('F j, Y'),
            '{day}' => now()->format('l'),
            '{time}' => now()->format('g:i A'),
        ];

        $vars = array_merge($defaults, $vars);

        foreach ($vars as $key => $value) {
            // Normalize key to {key} format
            $placeholder = Str::startsWith($key, '{') ? $key : '{' . $key . '}';
            $text = str_replace($placeholder, $value, $text);
        }

        return $text;
    }

    /**
     * Increment times_used counter.
     */
    public function markUsed(): void
    {
        $this->increment('times_used');
    }

    /**
     * Get available template categories.
     */
    public static function getCategories(): array
    {
        return [
            'personal' => 'شخصي',
            'business_intro' => 'تعريف عمل',
            'follow_up' => 'متابعة',
            'newsletter' => 'نشرة إخبارية',
            'friendly' => 'ودي',
            'thank_you' => 'شكر',
        ];
    }

    /**
     * Get body preview.
     */
    public function getBodyPreviewAttribute(): string
    {
        return Str::limit(strip_tags($this->body), 100);
    }
}
