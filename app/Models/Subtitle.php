<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subtitle extends Model
{
    protected $fillable = [
        'episode_id', 'language', 'label', 'file_path', 'is_default'
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function episode()
    {
        return $this->belongsTo(Episode::class);
    }
}
