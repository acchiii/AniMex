<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Episode extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'anime_id', 'title', 'number', 'number_decimal', 'synopsis',
        'thumbnail', 'duration', 'aired_at', 'is_filler', 'is_recap',
        'is_subbed', 'is_dubbed', 'is_premium_only', 'intro_start',
        'intro_end', 'views_count',
    ];

    protected $casts = [
        'aired_at'        => 'date',
        'is_filler'       => 'boolean',
        'is_recap'        => 'boolean',
        'is_subbed'       => 'boolean',
        'is_dubbed'       => 'boolean',
        'is_premium_only' => 'boolean',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function anime(): BelongsTo
    {
        return $this->belongsTo(Anime::class);
    }

    public function sources(): HasMany
    {
        return $this->hasMany(EpisodeSource::class)->orderBy('sort_order');
    }

    public function subtitles(): HasMany
    {
        return $this->hasMany(Subtitle::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function watchHistory(): HasMany
    {
        return $this->hasMany(WatchHistory::class);
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getThumbnailUrlAttribute(): string
    {
        if ($this->thumbnail) {
            return str_starts_with($this->thumbnail, 'http')
                ? $this->thumbnail
                : asset('storage/' . $this->thumbnail);
        }
        return $this->anime->cover_url ?? asset('images/placeholder-thumb.jpg');
    }

    public function getDurationFormattedAttribute(): string
    {
        if (!$this->duration) return 'N/A';
        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;
        return sprintf('%d:%02d', $minutes, $seconds);
    }

    public function getDisplayNumberAttribute(): string
    {
        return $this->number_decimal
            ? number_format($this->number_decimal, 1)
            : (string) $this->number;
    }

    public function getNextEpisodeAttribute(): ?self
    {
        return static::where('anime_id', $this->anime_id)
            ->where('number', '>', $this->number)
            ->orderBy('number')
            ->first();
    }

    public function getPreviousEpisodeAttribute(): ?self
    {
        return static::where('anime_id', $this->anime_id)
            ->where('number', '<', $this->number)
            ->orderByDesc('number')
            ->first();
    }

    public function getUserProgress(?int $userId): ?WatchHistory
    {
        if (!$userId) return null;
        return $this->watchHistory()->where('user_id', $userId)->first();
    }
}