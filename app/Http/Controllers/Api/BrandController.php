<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\BrandRequest;
use App\Http\Resources\Api\BrandResource;
use App\Http\Resources\Api\ProductResource;
use App\Models\Brand;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    public function index()
    {
        $brands = Brand::where('is_active', true)->get();
        return BrandResource::collection($brands);
    }

    public function adminIndex()
    {
        $brands = Brand::latest()->get();
        return BrandResource::collection($brands);
    }

    public function show(Brand $brand)
    {
        return response()->json([
            'brand' => new BrandResource($brand),
        ]);
    }

    public function store(BrandRequest $request)
    {
        $validated = $request->validated();

        $brand = Brand::create([
            'name' => $validated['name'],
            'logo' => $validated['logo'] ?? null,
            'description' => $validated['description'] ?? null,
            'slug' => $this->generateUniqueSlug($validated['slug'] ?? $validated['name']),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Brand created successfully',
            'brand' => new BrandResource($brand),
        ], 201);
    }

    public function update(BrandRequest $request, Brand $brand)
    {
        $validated = $request->validated();

        $brand->update(array_filter([
            'name' => $validated['name'] ?? null,
            'logo' => $validated['logo'] ?? null,
            'description' => $validated['description'] ?? null,
            'slug' => array_key_exists('slug', $validated) && $validated['slug']
                ? $this->generateUniqueSlug($validated['slug'], $brand->id)
                : (array_key_exists('name', $validated) ? $this->generateUniqueSlug($validated['name'], $brand->id) : null),
            'is_active' => $validated['is_active'] ?? null,
        ], static fn ($value) => !is_null($value)));

        return response()->json([
            'message' => 'Brand updated successfully',
            'brand' => new BrandResource($brand->fresh()),
        ]);
    }

    public function destroy(Brand $brand)
    {
        $brand->delete();

        return response()->json([
            'message' => 'Brand deleted successfully',
        ]);
    }

    public function products(Brand $brand)
    {
        $products = $brand->products()->with('category')->paginate(20);
        return ProductResource::collection($products);
    }

    private function generateUniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($value);
        $slug = $baseSlug;
        $counter = 1;

        while (
            Brand::where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter++;
        }

        return $slug;
    }

}
