<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['name', 'image', 'parent_id', 'slug', 'is_active'];

    protected function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    protected function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }


    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }


    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
