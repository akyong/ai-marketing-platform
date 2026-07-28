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
                $workspace->widget_key = 'ws_' . str_replace('-', '_', $workspace->slug ?? 'waperu') . '_' . substr(md5(uniqid()), 0, 8);
            }
        });
    }

    public function getWidgetKeyAttribute($value): string
    {
        if (empty($value) || $value === 'null') {
            $generated = 'ws_' . str_replace('-', '_', $this->slug ?? 'waperu') . '_' . substr(md5($this->id ?? 1), 0, 8);
            if ($this->exists && $this->getOriginal('widget_key') !== $generated) {
                $this->attributes['widget_key'] = $generated;
                \DB::table('workspaces')->where('id', $this->id)->update(['widget_key' => $generated]);
            }
            return $generated;
        }

        return $value;
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
