<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'sender',
        'message',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(CustomerChatSession::class, 'session_id');
    }
}
