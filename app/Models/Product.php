<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'images',
        'slug',
        'stock',
        'price',
        'discount_price',
        'is_featured',
        'view_count',
        'brand_id',
        'category_id',
        'is_active',
    ];


    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
    public function scopePopular($query)
    {
        return $query->orderBy('view_count', 'desc');
    }
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }
    public function wishlistedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'wishlists');
    }


    public function reviews()
    {
        return $this->hasMany(Review::class)->where('is_approved', true);
    }

    public function allReviews()
    {
        return $this->hasMany(Review::class);
    }

    // এভারেজ রেটিং ক্যালকুলেশন
    public function getAverageRatingAttribute()
    {
        return round($this->reviews()->avg('rating'), 1);
    }

    // রেটিং ডিস্ট্রিবিউশন (১-৫ তারকার সংখ্যা)
    public function getRatingDistributionAttribute()
    {
        // $distribution = [];
        // for ($i = 1; $i <= 5; $i++) {
        //     $distribution[$i] = $this->reviews()->where('rating', $i)->count();
        // }
        // return $distribution;

        return $this->reviews()
            ->selectRaw('rating, count(*) as total')
            ->groupBy('rating')
            ->pluck('total', 'rating')
            ->all();


    }

    // রিভিউ কাউন্ট
    public function getReviewsCountAttribute()
    {
        return $this->reviews()->count();
    }





}
