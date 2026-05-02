<?php
// ─────────────────────────────────────────────────────────
// User.php
// ─────────────────────────────────────────────────────────
namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'username', 'email', 'password', 'avatar', 'role',
        'subscription', 'subscription_ends_at', 'bio', 'country',
        'theme', 'email_notifications', 'push_notifications',
        'is_banned', 'ban_reason', 'last_seen_at', 'provider', 'provider_id',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at'    => 'datetime',
        'subscription_ends_at' => 'datetime',
        'last_seen_at'         => 'datetime',
        'is_banned'            => 'boolean',
        'email_notifications'  => 'boolean',
        'push_notifications'   => 'boolean',
        'password'             => 'hashed',
    ];

    public function watchHistory() { return $this->hasMany(WatchHistory::class); }
    public function favorites()    { return $this->hasMany(Favorite::class); }
    public function comments()     { return $this->hasMany(Comment::class); }
    public function ratings()      { return $this->hasMany(Rating::class); }
    public function subscriptions(){ return $this->hasMany(Subscription::class); }
    public function downloads()    { return $this->hasMany(Download::class); }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return str_starts_with($this->avatar, 'http')
                ? $this->avatar
                : asset('storage/avatars/' . $this->avatar);
        }
        $initial = urlencode(mb_substr($this->name, 0, 1));
        return "https://ui-avatars.com/api/?name={$initial}&background=6366f1&color=fff&size=128";
    }

    public function isPremium(): bool
    {
        return in_array($this->subscription, ['premium', 'vip'])
            && ($this->subscription_ends_at === null || $this->subscription_ends_at->isFuture());
    }

    public function isAdmin(): bool   { return $this->role === 'admin'; }
    public function isMod(): bool     { return in_array($this->role, ['admin', 'moderator']); }

    public function hasFavorited(int $animeId): bool
    {
        return $this->favorites()->where('anime_id', $animeId)->exists();
    }

    public function getWatchlistStatus(int $animeId): ?string
    {
        return $this->favorites()->where('anime_id', $animeId)->value('type');
    }

    public function getRouteKeyName(): string { return 'username'; }
}