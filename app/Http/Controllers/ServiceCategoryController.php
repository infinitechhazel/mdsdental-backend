<?php

namespace App\Http\Controllers;

use App\Models\ServiceCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class ServiceCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => ServiceCategory::all()
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:service_categories,name',
            'description' => 'required|string',
            'icon' => 'nullable|string',
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . preg_replace('/\s+/', '_', $image->getClientOriginalName());
            $image->move(public_path('images/services'), $imageName);
            $validated['image'] = 'images/services/' . $imageName;
        }

        $category = ServiceCategory::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully.',
            'data' => $category
        ], 201);
    }

    public function show(ServiceCategory $serviceCategory): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $serviceCategory
        ]);
    }

    public function update(Request $request, ServiceCategory $serviceCategory): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|unique:service_categories,name,' . $serviceCategory->id,
            'description' => 'sometimes|required|string',
            'icon' => 'nullable|string',
            'image' => 'sometimes|required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($request->hasFile('image')) {
            // Delete old image if it exists in public directory
            if ($serviceCategory->image && file_exists(public_path($serviceCategory->image))) {
                File::delete(public_path($serviceCategory->image));
            }

            $image = $request->file('image');
            $imageName = time() . '_' . preg_replace('/\s+/', '_', $image->getClientOriginalName());
            $image->move(public_path('images/services'), $imageName);
            $validated['image'] = 'images/services/' . $imageName;
        }

        $serviceCategory->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully.',
            'data' => $serviceCategory
        ]);
    }

    public function destroy(ServiceCategory $serviceCategory): JsonResponse
    {
        // Delete image file upon deletion
        if ($serviceCategory->image && file_exists(public_path($serviceCategory->image))) {
            File::delete(public_path($serviceCategory->image));
        }

        $serviceCategory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully.'
        ]);
    }
}