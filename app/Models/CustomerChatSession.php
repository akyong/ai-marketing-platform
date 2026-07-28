<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerChatSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'session_token',
        'visitor_name',
        'visitor_email',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CustomerChatMessage::class, 'session_id');
    }
}
