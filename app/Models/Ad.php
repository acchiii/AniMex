<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ad extends Model
{
    use HasFactory;

    protected $fillable = [
        'ad_position_id',
        'title',
        'content',
        'image_url',
        'target_url',
        'is_active',
        'starts_at',
        'ends_at',
        'clicks_count',
        'views_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function position(): BelongsTo
    {
        return $this->belongsTo(AdPosition::class, 'ad_position_id');
    }
}
