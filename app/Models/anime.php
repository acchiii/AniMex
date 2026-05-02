<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Scout\Searchable;

class Anime extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    protected $table = 'anime';

    protected $fillable = [
        'title', 'title_english', 'title_japanese', 'slug', 'synopsis',
        'cover_image', 'banner_image', 'trailer_url', 'type', 'status',
        'season', 'year', 'aired_from', 'aired_to', 'episodes_count',
        'episode_duration', 'rating', 'source', 'studio_id', 'score',
        'score_count', 'popularity', 'rank', 'favorites_count', 'views_count',
        'mal_id', 'anilist_id', 'is_featured', 'is_trending', 'is_subbed',
        'is_dubbed', 'is_premium_only', 'meta',
    ];

    protected $casts = [
        'aired_from'     => 'date',
        'aired_to'       => 'date',
        'is_featured'    => 'boolean',
        'is_trending'    => 'boolean',
        'is_subbed'      => 'boolean',
        'is_dubbed'      => 'boolean',
        'is_premium_only'=> 'boolean',
        'score'          => 'decimal:2',
        'meta'           => 'array',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function studio(): BelongsTo
    {
        return $this->belongsTo(Studio::class);
    }

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class)->orderBy('number');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function watchHistory(): HasMany
    {
        return $this->hasMany(WatchHistory::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeTrending(Builder $query): Builder
    {
        return $query->where('is_trending', true)->orderByDesc('views_count');
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeOngoing(Builder $query): Builder
    {
        return $query->where('status', 'ongoing');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopeTopRated(Builder $query): Builder
    {
        return $query->orderByDesc('score')->where('score_count', '>=', 10);
    }

    public function scopePopular(Builder $query): Builder
    {
        return $query->orderByDesc('views_count');
    }

public function scopeByGenre(Builder $query, string $genreSlug): Builder
    {
        return $query->whereHas('genres', fn($q) => $q->where('slug', $genreSlug));
    }

public function scopeByYear(Builder $query, int $year): Builder
    {
        return $query->where('year', $year);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getCoverUrlAttribute(): string
    {
        if ($this->cover_image) {
            return str_starts_with($this->cover_image, 'http')
                ? $this->cover_image
                : asset('storage/' . $this->cover_image);
        }
        return asset('images/placeholder-cover.jpg');
    }

    public function getBannerUrlAttribute(): string
    {
        if ($this->banner_image) {
            return str_starts_with($this->banner_image, 'http')
                ? $this->banner_image
                : asset('storage/' . $this->banner_image);
        }
        return $this->cover_url;
    }

    public function getScoreFormattedAttribute(): string
    {
return number_format((float) $this->score, 2);
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            'ongoing'   => 'green',
            'completed' => 'blue',
            'upcoming'  => 'yellow',
            'hiatus'    => 'red',
            default     => 'gray',
        };
    }

    public function getDisplayTitleAttribute(): string
    {
        return $this->title_english ?: $this->title;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // ─── Scout ───────────────────────────────────────────────────────────────

    public function toSearchableArray(): array
    {
        return [
            'id'             => $this->id,
            'title'          => $this->title,
            'title_english'  => $this->title_english,
            'title_japanese' => $this->title_japanese,
            'synopsis'       => $this->synopsis,
            'type'           => $this->type,
            'status'         => $this->status,
            'year'           => $this->year,
            'score'          => $this->score,
        ];
    }
}