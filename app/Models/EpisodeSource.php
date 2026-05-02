<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EpisodeSource extends Model
{
    protected $fillable = [
        'episode_id', 'video_server_id', 'label', 'quality', 
        'url', 'type', 'language', 'is_active', 'sort_order'
    ];

    protected $casts = [
        'is_active' => 'boolean',
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
