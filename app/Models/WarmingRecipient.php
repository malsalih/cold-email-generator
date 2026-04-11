<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarmingRecipient extends Model
{
    protected $fillable = ['email', 'name', 'group'];
}
