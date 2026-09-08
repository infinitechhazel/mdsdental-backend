<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File as FileRule;

class TeamController extends Controller
{
    /**
     * GET /api/teams
     *
     * Optional:
     * ?branch_id=sm-gensan   (branch slug)
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => [
                'nullable',
                'string',
                'exists:branches,branch_id',
            ],
        ]);

        $teams = Team::query()
            ->with('branch')
            ->when(
                $validated['branch_id'] ?? null,
                fn($query, $branchId) =>
                $query->whereHas(
                    'branch',
                    fn($q) => $q->where('branch_id', $branchId)
                )
            )
            ->orderBy('branch_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn(Team $team) => $this->teamResponse($team));

        return response()->json([
            'teams' => $teams,
        ]);
    }

    /**
     * POST /api/teams
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => [
                'required',
                'string',
                'exists:branches,branch_id',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'position' => [
                'nullable',
                'string',
                'max:255',
            ],

            'image' => [
                'nullable',
                FileRule::image()
                    ->types(['jpg', 'jpeg', 'png', 'webp'])
                    ->max('5mb'),
            ],
        ]);

        $branch = Branch::query()
            ->where('branch_id', $validated['branch_id'])
            ->firstOrFail();

        $sortOrder = (
            Team::query()
            ->where('branch_id', $branch->id)
            ->max('sort_order')
            ?? -1
        ) + 1;

        $imagePath = null;

        if ($request->hasFile('image')) {
            $imagePath = $this->storeImage(
                $request->file('image'),
                $branch->branch_id
            );
        }

        $team = Team::create([
            'branch_id' => $branch->id,
            'name' => $validated['name'],
            'position' => $validated['position'] ?? null,
            'image' => $imagePath,
            'sort_order' => $sortOrder,
        ]);

        $team->load('branch');

        return response()->json([
            'message' => 'Team member created successfully.',
            'team' => $this->teamResponse($team),
        ], 201);
    }

    /**
     * GET /api/teams/{id}
     */
    public function show(int $id): JsonResponse
    {
        $team = Team::query()
            ->with('branch')
            ->findOrFail($id);

        return response()->json([
            'team' => $this->teamResponse($team),
        ]);
    }

    /**
     * PUT /api/teams/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $team = Team::query()
            ->with('branch')
            ->findOrFail($id);

        $validated = $request->validate([
            'branch_id' => [
                'sometimes',
                'required',
                'string',
                'exists:branches,branch_id',
            ],

            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],

            'position' => [
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
                    ->types(['jpg', 'jpeg', 'png', 'webp'])
                    ->max('5mb'),
            ],
        ]);

        if (array_key_exists('branch_id', $validated)) {
            $branch = Branch::query()
                ->where('branch_id', $validated['branch_id'])
                ->firstOrFail();

            $team->branch_id = $branch->id;
        }

        if (array_key_exists('name', $validated)) {
            $team->name = $validated['name'];
        }

        if (array_key_exists('position', $validated)) {
            $team->position = $validated['position'];
        }

        if (array_key_exists('sort_order', $validated)) {
            $team->sort_order = $validated['sort_order'];
        }

        if ($request->hasFile('image')) {
            $oldPath = $team->image
                ? public_path($team->image)
                : null;

            if ($oldPath && is_file($oldPath)) {
                unlink($oldPath);
            }

            $team->image = $this->storeImage(
                $request->file('image'),
                $team->branch->branch_id
            );
        }

        $team->save();
        $team->load('branch');

        return response()->json([
            'message' => 'Team member updated successfully.',
            'team' => $this->teamResponse($team),
        ]);
    }

    /**
     * DELETE /api/teams/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $team = Team::findOrFail($id);

        if ($team->image) {
            $filePath = public_path($team->image);

            if (is_file($filePath)) {
                File::delete($filePath);
            }
        }

        $team->delete();

        return response()->json([
            'message' => 'Team member deleted successfully.',
        ]);
    }

    /**
     * Move an uploaded image into public/images/teams/{branchSlug}
     * and return the relative path.
     */
    private function storeImage($file, string $branchSlug): string
    {
        $directory = public_path("images/teams/{$branchSlug}");

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = Str::uuid() . '.' . $file->extension();

        $file->move($directory, $filename);

        return "images/teams/{$branchSlug}/{$filename}";
    }

    /**
     * Format team response.
     */
    private function teamResponse(Team $team): array
    {
        return [
            'id' => $team->id,
            'branchId' => $team->branch?->branch_id,
            'branchName' => $team->branch?->name,
            'name' => $team->name,
            'position' => $team->position,
            'image' => $team->image ? asset($team->image) : null,
            'sortOrder' => $team->sort_order,
        ];
    }
}
