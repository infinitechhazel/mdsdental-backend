<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Display a listing of products
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Product::query();

            // Search functionality
            if ($request->has('search') && ! empty($request->search)) {
                $query->search($request->search);
            }

            // Filter by category
            if ($request->has('category') && ! empty($request->category)) {
                $query->byCategory($request->category);
            }

            // Filter by best seller
            if ($request->has('best_seller') && $request->best_seller !== null) {
                $query->where('best_seller', $request->boolean('best_seller'));
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Handle limit parameter (for featured products, etc.)
            if ($request->has('limit')) {
                $limit = (int) $request->get('limit');
                $products = $query->limit($limit)->get();

                return response()->json($products);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);

            if ($request->has('paginate') && $request->paginate === 'false') {
                $products = $query->get();
            } else {
                $products = $query->paginate($perPage);
            }

            return response()->json($products);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch products',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created product
     */
    public function store(Request $request): JsonResponse
{
    try {

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'ingredients' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0|max:99999.99',
            'category' => 'required|string|max:100',
            'best_seller' => 'sometimes|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:20480',
            'set' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

       

        if ($request->hasFile('image')) {
            $data['image'] = $this->handleImageUpload($request->file('image'));
        }

        $product = Product::create($data);

        return response()->json([
            'message' => 'Product created successfully',
            'data' => $product,
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Failed to create product',
            'error' => $e->getMessage(),
        ], 500);
    }
}

    /**
     * Display the specified product
     */
    public function show(Product $product): JsonResponse
    {
        try {
            return response()->json($product);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified product
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        try {
            // Laravel automatically handles FormData requests (multipart/form-data)
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'ingredients' => 'nullable|string|max:1000',
                'price' => 'required|numeric|min:0|max:99999.99',
                'category' => 'required|string|max:100',
                'best_seller' => 'sometimes|in:true,false,1,0',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:22048',
                'set' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();

            // Convert boolean-like strings to actual booleans
            $data['best_seller'] = $this->convertToBoolean($request->input('best_seller', false));

            // Handle image upload from FormData
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                // Delete old image if exists
                if ($product->image) {
                    $this->deleteImage($product->image);
                }

                $data['image'] = $this->handleImageUpload($request->file('image'));
            }

            $product->update($data);

            return response()->json([
                'message' => 'Product updated successfully',
                'data' => $product->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified product
     */
    public function destroy(Product $product): JsonResponse
    {
        try {
            // Delete associated image
            if ($product->image) {
                $this->deleteImage($product->image);
            }

            $product->delete();

            return response()->json([
                'message' => 'Product deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get product categories
     */
    public function categories(): JsonResponse
    {
        try {
            $categories = Product::distinct()->pluck('category')->filter()->values();

            return response()->json($categories);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch categories',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Convert various boolean representations to actual boolean
     */
    private function convertToBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['true', '1', 'yes', 'on'], true);
        }

        if (is_numeric($value)) {
            return (bool) $value;
        }

        return false;
    }

    /**
     * Handle image upload using native PHP
     */
    private function handleImageUpload($file): string
    {
        // Generate unique filename
        $filename = time().'_'.Str::random(10).'.'.$file->getClientOriginalExtension();

        // Ensure the directory exists
        $publicPath = public_path('images/products');
        if (! file_exists($publicPath)) {
            mkdir($publicPath, 0755, true);
        }

        // Move the uploaded file to public directory
        $file->move($publicPath, $filename);

        return $filename;
    }

    /**
     * Handle image upload with basic resizing using native PHP GD
     */
    private function handleImageUploadWithResize($file): string
    {
        // Generate unique filename
        $filename = time().'_'.Str::random(10).'.'.$file->getClientOriginalExtension();

        // Ensure the directory exists
        $publicPath = public_path('images/products');
        if (! file_exists($publicPath)) {
            mkdir($publicPath, 0755, true);
        }

        // Get file info
        $originalPath = $file->getPathname();
        $imageInfo = getimagesize($originalPath);

        if (! $imageInfo) {
            // If not a valid image, just move it
            $file->move($publicPath, $filename);

            return $filename;
        }

        $originalWidth = $imageInfo[0];
        $originalHeight = $imageInfo[1];
        $imageType = $imageInfo[2];

        // Calculate new dimensions (max 800x800 while maintaining aspect ratio)
        $maxSize = 800;
        if ($originalWidth > $maxSize || $originalHeight > $maxSize) {
            if ($originalWidth > $originalHeight) {
                $newWidth = $maxSize;
                $newHeight = intval(($originalHeight * $maxSize) / $originalWidth);
            } else {
                $newHeight = $maxSize;
                $newWidth = intval(($originalWidth * $maxSize) / $originalHeight);
            }
        } else {
            $newWidth = $originalWidth;
            $newHeight = $originalHeight;
        }

        // Create image resource based on type
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($originalPath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($originalPath);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($originalPath);
                break;
            case IMAGETYPE_WEBP:
                $sourceImage = imagecreatefromwebp($originalPath);
                break;
            default:
                // Unsupported format, just move the file
                $file->move($publicPath, $filename);

                return $filename;
        }

        if (! $sourceImage) {
            // Failed to create image resource, just move the file
            $file->move($publicPath, $filename);

            return $filename;
        }

        // Create new image
        $newImage = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG and GIF
        if ($imageType == IMAGETYPE_PNG || $imageType == IMAGETYPE_GIF) {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
        }

        // Resize image
        imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

        // Save resized image
        $destinationPath = $publicPath.'/'.$filename;
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                imagejpeg($newImage, $destinationPath, 85);
                break;
            case IMAGETYPE_PNG:
                imagepng($newImage, $destinationPath, 8);
                break;
            case IMAGETYPE_GIF:
                imagegif($newImage, $destinationPath);
                break;
            case IMAGETYPE_WEBP:
                imagewebp($newImage, $destinationPath, 85);
                break;
        }

        // Clean up memory
        imagedestroy($sourceImage);
        imagedestroy($newImage);

        return $filename;
    }

    /**
     * Delete image from public directory
     */
    private function deleteImage(string $filename): void
    {
        $imagePath = public_path('images/products/'.$filename);
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }

    /**
     * Bulk delete products
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array',
                'ids.*' => 'integer|exists:products,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $products = Product::whereIn('id', $request->ids)->get();

            // Delete associated images
            foreach ($products as $product) {
                if ($product->image) {
                    $this->deleteImage($product->image);
                }
            }

            // Delete products
            Product::whereIn('id', $request->ids)->delete();

            return response()->json([
                'message' => 'Products deleted successfully',
                'deleted_count' => count($request->ids),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete products',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
