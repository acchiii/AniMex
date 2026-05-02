<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoServer extends Model
{
    protected $fillable = [
        'name', 'slug', 'type', 'base_url', 
        'priority', 'is_active', 'config'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'config' => 'array',
    ];

    public function sources()
    {
        return $this->hasMany(EpisodeSource::class);
    }
}
