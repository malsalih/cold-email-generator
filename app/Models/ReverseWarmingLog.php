<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReverseWarmingLog extends Model
{
    protected $fillable = [
        'reverse_warming_account_id',
        'target_email',
        'subject',
        'body',
        'status',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function account()
    {
        return $this->belongsTo(ReverseWarmingAccount::class, 'reverse_warming_account_id');
    }
}
