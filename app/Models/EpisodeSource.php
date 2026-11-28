<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EpisodeSource extends Model
{
    protected $fillable = [
        'episode_id', 'video_server_id', 'label', 'quality',
        'url', 'headers', 'metadata', 'provider', 'expires_at', 'failure_count',
        'last_failed_at', 'type', 'language', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'headers' => 'array',
        'metadata' => 'array',
        'expires_at' => 'datetime',
        'last_failed_at' => 'datetime',
    ];

    public function episode()
    {
        return $this->belongsTo(Episode::class);
    }

    public function server()
    {
        return $this->belongsTo(VideoServer::class, 'video_server_id');
    }
}
