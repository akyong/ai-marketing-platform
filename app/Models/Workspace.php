<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Workspace extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'widget_key',
    ];

    protected static function booted(): void
    {
        static::creating(function ($workspace) {
            if (empty($workspace->widget_key)) {
                $workspace->widget_key = 'ws_' . str_replace('-', '_', $workspace->slug) . '_' . substr(md5(uniqid()), 0, 8);
            }
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_user')->withPivot('role')->withTimestamps();
    }

    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class);
    }

    public function defaultBrand(): HasOne
    {
        return $this->hasOne(Brand::class)->latestOfMany();
    }
}
