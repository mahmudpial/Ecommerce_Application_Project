<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ProductRequest;
use App\Http\Resources\Api\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    // All products with filters
    public function index(Request $request)
    {
        $products = $this->buildProductQuery($request, true)->paginate(20);
        return ProductResource::collection($products);
    }

    // Admin products with filters
    public function adminIndex(Request $request)
    {
        $products = $this->buildProductQuery($request, false)->paginate(20);
        return ProductResource::collection($products);
    }

    // Latest products
    public function latest()
    {
        $products = Product::with(['brand', 'category'])->latest()->limit(10)->get();
        return ProductResource::collection($products);
    }

    // Popular products
    public function popular()
    {
        $products = Product::with(['brand', 'category'])->popular()->limit(10)->get();
        return ProductResource::collection($products);
    }

    // Featured products
    public function featured()
    {
        $products = Product::with(['brand', 'category'])->featured()->latest()->limit(10)->get();
        return ProductResource::collection($products);
    }

    // Single product details
    public function show(Product $product)
    {
        $product->load(['brand', 'category']);

        // Increment view count
        $product->increment('view_count');

        // Related products (same category)
        $related = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->latest()
            ->limit(5)
            ->get();

        return response()->json([
            'product' => new ProductResource($product),
            'related_products' => ProductResource::collection($related),
        ]);
    }

    public function adminShow(Product $product)
    {
        return response()->json([
            'product' => new ProductResource($product->load(['brand', 'category'])),
        ]);
    }

    public function store(ProductRequest $request)
    {
        $validated = $request->validated();

        if (isset($validated['images']) && is_array($validated['images'])) {
            $validated['images'] = json_encode($validated['images']);
        }

        $validated['slug'] = $this->generateUniqueSlug(
            $validated['slug'] ?? $validated['name']
        );

        $product = Product::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'images' => $validated['images'] ?? null,
            'slug' => $validated['slug'],
            'stock' => $validated['stock'] ?? 0,
            'price' => $validated['price'] ?? null,
            'discount_price' => $validated['discount_price'] ?? null,
            'is_featured' => $validated['is_featured'] ?? false,
            'brand_id' => $validated['brand_id'],
            'category_id' => $validated['category_id'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Product created successfully',
            'product' => new ProductResource($product->load(['brand', 'category'])),
        ], 201);
    }

    public function update(ProductRequest $request, Product $product)
    {
        $validated = $request->validated();

        if (isset($validated['images']) && is_array($validated['images'])) {
            $validated['images'] = json_encode($validated['images']);
        }

        if (array_key_exists('slug', $validated) && $validated['slug']) {
            $validated['slug'] = $this->generateUniqueSlug(
                $validated['slug'],
                $product->id
            );
        } elseif (array_key_exists('name', $validated)) {
            $validated['slug'] = $this->generateUniqueSlug(
                $validated['name'],
                $product->id
            );
        }

        $product->update(array_filter([
            'name' => $validated['name'] ?? null,
            'description' => $validated['description'] ?? null,
            'images' => $validated['images'] ?? null,
            'slug' => $validated['slug'] ?? null,
            'stock' => $validated['stock'] ?? null,
            'price' => $validated['price'] ?? null,
            'discount_price' => $validated['discount_price'] ?? null,
            'is_featured' => $validated['is_featured'] ?? null,
            'brand_id' => $validated['brand_id'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'is_active' => $validated['is_active'] ?? null,
        ], static fn ($value) => !is_null($value)));

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => new ProductResource($product->fresh()->load(['brand', 'category'])),
        ]);
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully',
        ]);
    }

    private function generateUniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($value);
        $slug = $baseSlug;
        $counter = 1;

        while (
            Product::where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter++;
        }

        return $slug;
    }

    private function buildProductQuery(Request $request, bool $activeOnly = true)
    {
        $query = Product::with(['brand', 'category']);

        if ($activeOnly) {
            $query->where('is_active', true);
        } elseif ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if (! $activeOnly && $request->filled('is_featured')) {
            $query->where('is_featured', $request->boolean('is_featured'));
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if (! $activeOnly && $request->filled('stock_min')) {
            $query->where('stock', '>=', (int) $request->stock_min);
        }
        if (! $activeOnly && $request->filled('stock_max')) {
            $query->where('stock', '<=', (int) $request->stock_max);
        }

        return $query;
    }

}
