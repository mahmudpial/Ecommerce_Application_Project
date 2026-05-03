<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CategoryRequest;
use App\Http\Resources\Api\CategoryResource;
use App\Http\Resources\Api\ProductResource;
use App\Models\Category;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::where('is_active', true)->get();
        return CategoryResource::collection($categories);
    }

    public function adminIndex()
    {
        $categories = Category::latest()->get();
        return CategoryResource::collection($categories);
    }

    public function show(Category $category)
    {
        return response()->json([
            'category' => new CategoryResource($category),
        ]);
    }

    public function store(CategoryRequest $request)
    {
        $validated = $request->validated();

        $category = Category::create([
            'name' => $validated['name'],
            'image' => $validated['image'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
            'slug' => $this->generateUniqueSlug($validated['slug'] ?? $validated['name']),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Category created successfully',
            'category' => new CategoryResource($category),
        ], 201);
    }

    public function update(CategoryRequest $request, Category $category)
    {
        $validated = $request->validated();

        $category->update(array_filter([
            'name' => $validated['name'] ?? null,
            'image' => $validated['image'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
            'slug' => array_key_exists('slug', $validated) && $validated['slug']
                ? $this->generateUniqueSlug($validated['slug'], $category->id)
                : (array_key_exists('name', $validated) ? $this->generateUniqueSlug($validated['name'], $category->id) : null),
            'is_active' => $validated['is_active'] ?? null,
        ], static fn ($value) => !is_null($value)));

        return response()->json([
            'message' => 'Category updated successfully',
            'category' => new CategoryResource($category->fresh()),
        ]);
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully',
        ]);
    }

    public function products(Category $category)
    {
        $products = $category->products()->with('brand')->paginate(20);
        return ProductResource::collection($products);
    }

    private function generateUniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($value);
        $slug = $baseSlug;
        $counter = 1;

        while (
            Category::where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter++;
        }

        return $slug;
    }

}
