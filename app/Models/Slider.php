<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Slider extends Model
{
    protected $fillable = ['title', 'description', 'image', 'slug', 'link', 'is_active', 'order'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
