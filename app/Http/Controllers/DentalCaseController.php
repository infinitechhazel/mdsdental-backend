<?php

namespace App\Http\Controllers;

use App\Models\DentalCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DentalCaseController extends Controller
{
    /**
     * Display a listing of cases, with optional search + category filter,
     * paginated for the admin table.
     */
    public function index(Request $request): JsonResponse
    {
        $cases = DentalCase::query()
            ->when(
                $request->filled('category') && $request->category !== 'All',
                fn ($query) => $query->where('category', $request->category)
            )
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where(function ($q) use ($request) {
                    $term = $request->search;
                    $q->where('title', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%");
                })
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return response()->json($cases);
    }

    /**
     * Store a newly created case.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'before' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'after' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'note' => ['nullable', 'string'],
        ]);

        $beforePath = $this->uploadImage($request->file('before'), 'before');
        $afterPath = $this->uploadImage($request->file('after'), 'after');

        $case = DentalCase::create([
            'title' => $validated['title'],
            'category' => $validated['category'],
            'description' => $validated['description'],
            'before' => $beforePath,
            'after' => $afterPath,
            'note' => $validated['note'] ?? null,
        ]);

        return response()->json($case, 201);
    }

    /**
     * Display the specified case.
     */
    public function show(DentalCase $dentalCase): JsonResponse
    {
        return response()->json($dentalCase);
    }

    /**
     * Update the specified case.
     */
    public function update(Request $request, DentalCase $dentalCase): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'category' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string'],
            'before' => ['sometimes', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'after' => ['sometimes', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'note' => ['nullable', 'string'],
        ]);

        $data = [
            'title' => $validated['title'] ?? $dentalCase->title,
            'category' => $validated['category'] ?? $dentalCase->category,
            'description' => $validated['description'] ?? $dentalCase->description,
            'note' => $validated['note'] ?? $dentalCase->note,
        ];

        if ($request->hasFile('before')) {
            $this->deleteImage($dentalCase->before);
            $data['before'] = $this->uploadImage($request->file('before'), 'before');
        }

        if ($request->hasFile('after')) {
            $this->deleteImage($dentalCase->after);
            $data['after'] = $this->uploadImage($request->file('after'), 'after');
        }

        $dentalCase->update($data);

        return response()->json($dentalCase);
    }

    /**
     * Remove the specified case.
     */
    public function destroy(DentalCase $dentalCase): JsonResponse
    {
        $this->deleteImage($dentalCase->before);
        $this->deleteImage($dentalCase->after);

        $dentalCase->delete();

        return response()->json([
            'message' => 'Case deleted successfully.',
        ]);
    }

    /**
     * Upload image to public/images/dental-cases.
     */
    private function uploadImage($file, string $type): string
    {
        $directory = public_path('images/dental-cases');

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = $type . '-' . Str::uuid() . '.' . $file->getClientOriginalExtension();

        $file->move($directory, $filename);

        return '/images/dental-cases/' . $filename;
    }

    /**
     * Delete image from public/images/dental-cases.
     */
    private function deleteImage(?string $path): void
    {
        if (!$path) {
            return;
        }

        $filePath = public_path($path);

        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}