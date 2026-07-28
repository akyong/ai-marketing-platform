<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'name',
        'website_url',
        'description',
        'industry',
        'target_audience',
        'tone_of_voice',
        'social_links',
    ];

    protected $casts = [
        'social_links' => 'array',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
