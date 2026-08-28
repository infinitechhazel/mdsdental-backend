<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class BranchController extends Controller
{
    /**
     * GET /api/branches
     */
    public function index(): JsonResponse
    {
        $branches = Branch::query()
            ->with('images')
            ->orderBy('name')
            ->get()
            ->map(fn(Branch $branch) => $this->branchResponse($branch))
            ->values();

        return response()->json([
            'branches' => $branches,
        ]);
    }

    /**
     * GET /api/branches/{branchId}
     */
    public function show(string $branchId): JsonResponse
    {
        $branch = Branch::query()
            ->with('images')
            ->where('branch_id', $branchId)
            ->firstOrFail();

        return response()->json([
            'branch' => $this->branchResponse($branch),
        ]);
    }

    /**
     * POST /api/branches
     *
     * branch_id is generated automatically from name.
     *
     * Example:
     * "SM City General Santos"
     * => "sm-city-general-santos"
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'area' => [
                'nullable',
                'string',
                'max:255',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:100',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'address' => [
                'nullable',
                'string',
            ],

            'hours' => [
                'nullable',
                'string',
                'max:255',
            ],

            'map_query' => [
                'nullable',
                'string',
            ],

            'directions_url' => [
                'nullable',
                'url',
                'max:2048',
            ],

            'blurb' => [
                'nullable',
                'string',
            ],

            'facebook' => [
                'nullable',
                'url',
                'max:2048',
            ],

            'instagram' => [
                'nullable',
                'url',
                'max:2048',
            ],
        ]);

        $branchId = Str::slug($validated['name']);

        /*
         * Make sure the generated slug is usable.
         */
        if ($branchId === '') {
            return response()->json([
                'message' => 'Unable to generate a branch ID from the branch name.',
            ], 422);
        }

        /*
         * branch_id must be unique.
         */
        if (Branch::where('branch_id', $branchId)->exists()) {
            return response()->json([
                'message' => 'A branch with this name already exists.',
                'errors' => [
                    'name' => [
                        'A branch with this name already exists.',
                    ],
                ],
            ], 422);
        }

        $branch = Branch::create([
            'branch_id' => $branchId,
            ...$validated,
        ]);

        $branch->load('images');

        return response()->json([
            'message' => 'Branch created successfully.',
            'branch' => $this->branchResponse($branch),
        ], 201);
    }

    /**
     * PUT /api/branches/{branchId}
     *
     * branch_id is regenerated automatically if the name changes.
     */
    public function update(
        Request $request,
        string $branchId
    ): JsonResponse {
        $branch = Branch::query()
            ->where('branch_id', $branchId)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],

            'area' => [
                'nullable',
                'string',
                'max:255',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:100',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'address' => [
                'nullable',
                'string',
            ],

            'hours' => [
                'nullable',
                'string',
                'max:255',
            ],

            'map_query' => [
                'nullable',
                'string',
            ],

            'directions_url' => [
                'nullable',
                'url',
                'max:2048',
            ],

            'blurb' => [
                'nullable',
                'string',
            ],

            'facebook' => [
                'nullable',
                'url',
                'max:2048',
            ],

            'instagram' => [
                'nullable',
                'url',
                'max:2048',
            ],
        ]);

        /*
         * Only regenerate the branch_id when
         * the branch name is included in the update.
         */
        if (
            array_key_exists('name', $validated) &&
            $validated['name'] !== $branch->name
        ) {
            $newBranchId = Str::slug($validated['name']);

            if ($newBranchId === '') {
                return response()->json([
                    'message' => 'Unable to generate a branch ID from the branch name.',
                ], 422);
            }

            /*
             * Check whether another branch already owns
             * the generated slug.
             */
            $slugExists = Branch::query()
                ->where('branch_id', $newBranchId)
                ->whereKeyNot($branch->id)
                ->exists();

            if ($slugExists) {
                return response()->json([
                    'message' => 'A branch with this name already exists.',
                    'errors' => [
                        'name' => [
                            'A branch with this name already exists.',
                        ],
                    ],
                ], 422);
            }

            $validated['branch_id'] = $newBranchId;
        }

        DB::transaction(function () use ($branch, $validated) {
            $branch->update($validated);
        });

        $branch->load('images');

        return response()->json([
            'message' => 'Branch updated successfully.',
            'branch' => $this->branchResponse($branch),
        ]);
    }

    /**
     * DELETE /api/branches/{branchId}
     */
    public function destroy(string $branchId): JsonResponse
    {
        $branch = Branch::query()
            ->with('images')
            ->where('branch_id', $branchId)
            ->firstOrFail();

        /*
         * Delete physical image files.
         */
        foreach ($branch->images as $image) {
            $filePath = public_path($image->path);

            if (is_file($filePath)) {
                File::delete($filePath);
            }
        }

        /*
         * Delete the branch.
         *
         * branch_images are removed automatically through
         * cascadeOnDelete().
         */
        $branch->delete();

        /*
         * Remove the branch's image directory.
         */
        $directory = public_path(
            "images/branches/{$branchId}"
        );

        if (is_dir($directory)) {
            File::deleteDirectory($directory);
        }

        return response()->json([
            'message' => 'Branch deleted successfully.',
        ]);
    }

    /**
     * Format branch response.
     */
    private function branchResponse(Branch $branch): array
    {
        return [
            'id' => $branch->id,

            'branch_id' => $branch->branch_id,

            'name' => $branch->name,

            'area' => $branch->area,

            'phone' => $branch->phone,

            'email' => $branch->email,

            'address' => $branch->address,

            'hours' => $branch->hours,

            'mapQuery' => $branch->map_query,

            'directionsUrl' => $branch->directions_url,

            'blurb' => $branch->blurb,

            'facebook' => $branch->facebook,

            'instagram' => $branch->instagram,

            'images' => $branch->images
                ->map(fn($image) => [
                    'id' => $image->id,
                    'branch_id' => $image->branch_id,
                    'type' => $image->type,
                    'url' => asset($image->path),
                    'alt' => $image->alt,
                    'sort_order' => $image->sort_order,
                ])
                ->values()
                ->all(),
        ];
    }
}
