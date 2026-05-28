<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Service::query();

        // SEARCH
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        // FILTER CATEGORY
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // FILTER STATUS
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $services = $query->latest()->paginate(10)->withQueryString();

        return response()->json($services);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:Dental,Aesthetic',
            'price' => 'required|numeric|min:0',
            'status' => 'required|in:Active,Inactive',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // HANDLE IMAGE
        if ($request->hasFile('image')) {
            $validated['image'] = $this->handleImageUpload($request->file('image'));
        }

        $service = Service::create($validated);

        return response()->json([
            'message' => 'Service created successfully.',
            'data' => $service,
        ], 201);
    }

    public function show(Service $service)
    {
        return response()->json($service);
    }

    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:Dental,Aesthetic',
            'price' => 'required|numeric|min:0',
            'status' => 'required|in:Active,Inactive',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // HANDLE IMAGE UPDATE
        if ($request->hasFile('image')) {

            // DELETE OLD IMAGE (FIXED)
            if ($service->image) {
                $oldPath = public_path('images/services/' . $service->image);

                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }

            $validated['image'] = $this->handleImageUpload($request->file('image'));
        }

        $service->update($validated);

        return response()->json([
            'message' => 'Service updated successfully.',
            'data' => $service,
        ]);
    }

    public function destroy(Service $service)
    {
        // DELETE IMAGE (FIXED)
        if ($service->image) {
            $oldPath = public_path('images/services/' . $service->image);

            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }

        $service->delete();

        return response()->json([
            'message' => 'Service deleted successfully.',
        ]);
    }

    private function handleImageUpload($file): string
    {
        $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();

        $publicPath = public_path('images/services');

        if (!file_exists($publicPath)) {
            mkdir($publicPath, 0755, true);
        }

        $file->move($publicPath, $filename);

        return '/images/services/' . $filename;
    }
}