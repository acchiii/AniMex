<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'price', 'currency', 
        'billing_period', 'features', 'remove_ads', 'allow_downloads', 
        'allow_hd', 'is_active', 'sort_order'
    ];

    protected $casts = [
        'features' => 'array',
        'remove_ads' => 'boolean',
        'allow_downloads' => 'boolean',
        'allow_hd' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }
}
