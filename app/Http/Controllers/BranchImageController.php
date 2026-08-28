<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File as FileRule;

class BranchImageController extends Controller
{
    /**
     * GET /api/branch-images
     *
     * Optional:
     * ?branch_id=1
     * ?type=clinic
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => [
                'nullable',
                'integer',
                'exists:branches,id',
            ],

            'type' => [
                'nullable',
                'in:clinic,team',
            ],
        ]);

        $images = BranchImage::query()
            ->with('branch')
            ->when(
                $validated['branch_id'] ?? null,
                fn($query, $branchId) =>
                $query->where('branch_id', $branchId)
            )
            ->when(
                $validated['type'] ?? null,
                fn($query, $type) =>
                $query->where('type', $type)
            )
            ->orderBy('branch_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(
                fn(BranchImage $image) =>
                $this->imageResponse($image)
            );

        return response()->json([
            'images' => $images,
        ]);
    }

    /**
     * POST /api/branches/{branchId}/images
     */
    public function store(
        Request $request,
        string $branchId
    ): JsonResponse {
        /*
         * branchId here is the public slug:
         *
         * /api/branches/sm-gensan/images
         */
        $branch = Branch::query()
            ->where('branch_id', $branchId)
            ->firstOrFail();

        $validated = $request->validate([
            'type' => [
                'required',
                'in:clinic,team',
            ],

            'images' => [
                'required',
                'array',
                'min:1',
            ],

            'images.*' => [
                'required',
                FileRule::image()
                    ->types([
                        'jpg',
                        'jpeg',
                        'png',
                        'webp',
                    ])
                    ->max('5mb'),
            ],
        ]);

        $type = $validated['type'];

        $sortOrder = (
            BranchImage::query()
            ->where('branch_id', $branch->id)
            ->where('type', $type)
            ->max('sort_order')
            ?? -1
        ) + 1;

        /*
         * Files are still organized using the public branch slug.
         */
        $directory = public_path(
            "images/branches/{$branch->branch_id}/{$type}"
        );

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $createdImages = [];

        foreach ($validated['images'] as $file) {
            $filename = Str::uuid() . '.' . $file->extension();

            $file->move(
                $directory,
                $filename
            );

            $image = BranchImage::create([
                /*
                 * IMPORTANT:
                 *
                 * Store branches.id, not branches.branch_id.
                 */
                'branch_id' => $branch->id,

                'type' => $type,

                'path' =>
                "images/branches/{$branch->branch_id}/{$type}/{$filename}",

                'alt' =>
                "{$branch->name} {$type} image",

                'sort_order' => $sortOrder++,
            ]);

            $createdImages[] = $this->imageResponse($image);
        }

        return response()->json([
            'message' => 'Images uploaded successfully.',
            'images' => $createdImages,
        ], 201);
    }

    /**
     * GET /api/branch-images/{imageId}
     */
    public function show(int $imageId): JsonResponse
    {
        $image = BranchImage::query()
            ->with('branch')
            ->findOrFail($imageId);

        return response()->json([
            'image' => $this->imageResponse($image),
        ]);
    }

    /**
     * PUT /api/branch-images/{imageId}
     */
    public function update(
        Request $request,
        int $imageId
    ): JsonResponse {
        $image = BranchImage::query()
            ->with('branch')
            ->findOrFail($imageId);

        $validated = $request->validate([
            'alt' => [
                'nullable',
                'string',
                'max:255',
            ],

            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'image' => [
                'nullable',
                FileRule::image()
                    ->types([
                        'jpg',
                        'jpeg',
                        'png',
                        'webp',
                    ])
                    ->max('5mb'),
            ],
        ]);

        if (array_key_exists('alt', $validated)) {
            $image->alt = $validated['alt'];
        }

        if (array_key_exists('sort_order', $validated)) {
            $image->sort_order = $validated['sort_order'];
        }

        if ($request->hasFile('image')) {
            $file = $request->file('image');

            /*
             * Delete old file.
             */
            $oldPath = public_path($image->path);

            if (is_file($oldPath)) {
                unlink($oldPath);
            }

            /*
             * Get the branch through the stable FK.
             */
            $branch = $image->branch;

            $directory = public_path(
                "images/branches/{$branch->branch_id}/{$image->type}"
            );

            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            $filename = Str::uuid() . '.' . $file->extension();

            $file->move(
                $directory,
                $filename
            );

            $image->path =
                "images/branches/{$branch->branch_id}/{$image->type}/{$filename}";
        }

        $image->save();

        return response()->json([
            'message' => 'Image updated successfully.',
            'image' => $this->imageResponse($image),
        ]);
    }

    /**
     * DELETE /api/branch-images/{imageId}
     */
    public function destroy(int $imageId): JsonResponse
    {
        $image = BranchImage::findOrFail($imageId);

        $filePath = public_path($image->path);

        if (is_file($filePath)) {
            unlink($filePath);
        }

        $image->delete();

        return response()->json([
            'message' => 'Image deleted successfully.',
        ]);
    }

    /**
     * Format image response.
     */
    private function imageResponse(
        BranchImage $image
    ): array {
        return [
            'id' => $image->id,

            /*
             * Return the public branch slug to the frontend.
             */
            'branch_id' => $image->branch?->branch_id,

            'type' => $image->type,

            'url' => asset($image->path),

            'alt' => $image->alt,

            'sort_order' => $image->sort_order,
        ];
    }
}
