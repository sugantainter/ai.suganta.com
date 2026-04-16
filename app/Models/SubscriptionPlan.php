<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'subscription_plans';
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'billing_period',
        'max_images',
        'max_files',
        'features',
        'is_popular',
        'is_active',
        'sort_order',
        's_type',
        'max_listings',
        'ai_tokens',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'max_images' => 'integer',
        'max_files' => 'integer',
        'features' => 'array',
        'is_popular' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        's_type' => 'integer',
        'max_listings' => 'integer',
        'ai_tokens' => 'integer',
    ];

    public function userSubscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class, 'subscription_plan_id');
    }
}
