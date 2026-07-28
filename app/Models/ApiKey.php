<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'provider',
        'key_name',
        'api_key',
        'is_active',
        'usage_count',
        'error_count',
        'last_used_at',
        'quota_exceeded_until',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'usage_count' => 'integer',
        'error_count' => 'integer',
        'last_used_at' => 'datetime',
        'quota_exceeded_until' => 'datetime',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function markUsed(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    public function markQuotaExceeded(int $hoursToCooldown = 1): void
    {
        $this->increment('error_count');
        $this->update([
            'quota_exceeded_until' => now()->addHours($hoursToCooldown),
        ]);
    }
}
