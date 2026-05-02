<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\BrandResource;
use App\Http\Resources\Api\ProductResource;
use App\Models\Brand;

class BrandController extends Controller
{
    public function index()
    {
        $brands = Brand::where('is_active', true)->get();
        return BrandResource::collection($brands);
    }

    public function products(Brand $brand)
    {
        $products = $brand->products()->with('category')->paginate(20);
        return ProductResource::collection($products);
    }

}
