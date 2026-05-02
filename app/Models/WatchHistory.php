<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WatchHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'anime_id',
        'episode_id',
        'progress',
        'duration',
        'completed',
        'watched_at',
    ];

    protected $casts = [
        'completed' => 'boolean',
        'watched_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function anime(): BelongsTo
    {
        return $this->belongsTo(Anime::class);
    }

    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }
}
