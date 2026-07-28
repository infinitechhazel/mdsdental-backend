<?php

// app/Http/Controllers/Api/FacilityController.php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class FacilityController extends Controller
{
    // ─── Destination folder inside Laravel's /public ───────────────────────────
    // Images will be served as: https://yourdomain.com/images/facilities/<filename>
    private const IMAGE_DIR = 'images/facilities';

    /* ── GET /api/facilities ───────────────────────────────────────────────── */
    public function index(): JsonResponse
    {
        $facilities = Facility::orderBy('sort_order')->get();
        return response()->json($facilities);
    }

    /* ── GET /api/facilities/{id} ──────────────────────────────────────────── */
    public function show(Facility $facility): JsonResponse
    {
        return response()->json($facility);
    }

    /* ── POST /api/facilities ──────────────────────────────────────────────── */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'icon_name'   => 'required|string|max:50',
            'label'       => 'required|string|max:80',
            'name'        => 'required|string|max:200',
            'description' => 'required|string',
            'bullets'     => 'nullable|string',   // JSON-encoded array from FormData
            'accent'      => 'required|in:cyan,blue',
            'sort_order'  => 'nullable|integer|min:0',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $this->saveImageToPublic($request->file('image'));
        }

        $facility = Facility::create([
            'icon_name'   => $request->icon_name,
            'label'       => $request->label,
            'name'        => $request->name,
            'description' => $request->description,
            'bullets'     => $request->bullets ? json_decode($request->bullets, true) : [],
            'accent'      => $request->accent,
            'sort_order'  => $request->sort_order ?? 0,
            'image_path'  => $imagePath,
        ]);

        return response()->json($facility, 201);
    }

    /* ── POST /api/facilities/{id}  (with _method=PUT) ──────────────────────── */
    public function update(Request $request, Facility $facility): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'icon_name'   => 'sometimes|required|string|max:50',
            'label'       => 'sometimes|required|string|max:80',
            'name'        => 'sometimes|required|string|max:200',
            'description' => 'sometimes|required|string',
            'bullets'     => 'nullable|string',
            'accent'      => 'sometimes|required|in:cyan,blue',
            'sort_order'  => 'nullable|integer|min:0',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only(['icon_name', 'label', 'name', 'description', 'accent', 'sort_order']);

        if ($request->has('bullets')) {
            $data['bullets'] = $request->bullets ? json_decode($request->bullets, true) : [];
        }

        if ($request->hasFile('image')) {
            // Delete old image from public folder (if it exists)
            $this->deleteImageFromPublic($facility->image_path);

            $data['image_path'] = $this->saveImageToPublic($request->file('image'));
        }

        $facility->update($data);

        return response()->json($facility->fresh());
    }

    /* ── DELETE /api/facilities/{id} ────────────────────────────────────────── */
    public function destroy(Facility $facility): JsonResponse
    {
        // Remove image file from public folder
        $this->deleteImageFromPublic($facility->image_path);

        $facility->delete();

        return response()->json(['message' => 'Facility deleted successfully.']);
    }

    /* ─────────────────────────────────────────────────────────────────────────
       PRIVATE HELPERS — public-path image management
       Images are stored inside Laravel's /public directory so they are
       served directly by the web server without any symlink.
    ───────────────────────────────────────────────────────────────────────── */

    /**
     * Save an uploaded image to /public/images/facilities/<uuid>.<ext>
     * and return the relative web path (without leading slash).
     */
    private function saveImageToPublic(\Illuminate\Http\UploadedFile $file): string
    {
        $dir  = public_path(self::IMAGE_DIR);

        // Create the directory if it does not exist
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $file->move($dir, $filename);

        return self::IMAGE_DIR . '/' . $filename;
        // e.g. "images/facilities/550e8400-e29b-41d4-a716-446655440000.jpg"
    }

    /**
     * Delete an image file from /public given a stored relative path.
     * Silently ignores missing files.
     */
    private function deleteImageFromPublic(?string $relativePath): void
    {
        if (!$relativePath) {
            return;
        }

        $absolutePath = public_path($relativePath);

        if (file_exists($absolutePath)) {
            unlink($absolutePath);
        }
    }
}