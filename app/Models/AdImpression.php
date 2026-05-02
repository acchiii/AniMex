<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdImpression extends Model
{
    protected $fillable = [
        'ad_id', 'user_id', 'ip_address', 'user_agent', 
        'page_url', 'event', 'country'
    ];

    public function ad()
    {
        return $this->belongsTo(Ad::class);
    }
}
