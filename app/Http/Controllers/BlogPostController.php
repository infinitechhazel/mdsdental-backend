<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BlogPostController extends Controller
{
    private function ensureDirectoryExists(string $path): void
    {
        if (! is_dir($path)) {
            mkdir($path, 0777, true);
        }
    }

    /**
     * Get all blog posts
     */
    public function index(Request $request)
    {
        $query = BlogPost::query();

        // only filter if draft query exists
        if ($request->has('draft')) {
            $query->where('draft', $request->draft);
        }

        return $query->latest()->get();
    }

    /**
     * Show single blog post
     */
    public function show(BlogPost $blogPost)
    {
        return $blogPost;
    }

    /**
     * Store a new blog post with direct file uploads
     */
    public function store(Request $request)
    {
        try {

            $validated = $request->validate([
                'title' => 'required|string',
                'excerpt' => 'required|string',
                'content' => 'required|string',
                'author' => 'required|string',
                'video_url' => 'nullable|string',
                'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:20480',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:20480',
                'draft' => 'nullable|boolean',
            ]);

            $imagePath = public_path('images/blog');
            $thumbPath = public_path('images/blog/thumbnails');

            $this->ensureDirectoryExists($imagePath);
            $this->ensureDirectoryExists($thumbPath);

            $hasVideo = ! empty($validated['video_url']);
            $hasImageFile = $request->hasFile('image');

            // CASE 1: VIDEO POST (video + thumbnail allowed)
            if ($hasVideo) {

                // IMAGE MUST BE REMOVED
                if ($request->hasFile('image')) {
                    $validated['image'] = null;
                }

            }

            // CASE 2: IMAGE POST (image only)
            if ($hasImageFile) {
                $validated['video_url'] = null;
                $request->files->remove('thumbnail');
                $validated['thumbnail'] = null;
            }

            /*
            IMAGE UPLOAD
            */
            if ($request->hasFile('image') && empty($validated['video_url'])) {

                $file = $request->file('image');
                $filename = time().'_'.$file->getClientOriginalName();

                $file->move($imagePath, $filename);

                $validated['image'] = '/images/blog/'.$filename;
            }

            /*
            THUMBNAIL UPLOAD (ONLY FOR VIDEO POST)
            */
            if ($request->hasFile('thumbnail') && ! empty($validated['video_url'])) {

                $thumb = $request->file('thumbnail');
                $thumbName = time().'_thumb_'.$thumb->getClientOriginalName();

                $thumb->move($thumbPath, $thumbName);

                $validated['thumbnail'] = '/images/blog/thumbnails/'.$thumbName;
            }

            $blogPost = BlogPost::create($validated);

            return response()->json($blogPost, 201);

        } catch (ValidationException $e) {

            return response()->json([
                'error' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create blog post',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update existing blog post
     */
    public function update(Request $request, BlogPost $blogPost)
    {
        try {
            $validated = $request->validate([
                'title' => 'string',
                'excerpt' => 'string',
                'content' => 'string',
                'author' => 'string',
                'video_url' => 'nullable|string',
                'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:20480',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:20480',
                'draft' => 'nullable|boolean',
            ]);

            // FOLDERS
            $imagePath = public_path('images/blog');
            $thumbPath = public_path('thumbnails/blog');

            $this->ensureDirectoryExists($imagePath);
            $this->ensureDirectoryExists($thumbPath);

            // IMAGE UPDATE
            if ($request->hasFile('image')) {

                if ($blogPost->image && file_exists(public_path($blogPost->image))) {
                    unlink(public_path($blogPost->image));
                }

                $file = $request->file('image');
                $filename = time().'_'.$file->getClientOriginalName();

                $file->move($imagePath, $filename);

                $validated['image'] = '/images/blog/'.$filename;
            }

            // THUMBNAIL UPDATE
            if ($request->hasFile('thumbnail')) {

                if ($blogPost->thumbnail && file_exists(public_path($blogPost->thumbnail))) {
                    unlink(public_path($blogPost->thumbnail));
                }

                $thumb = $request->file('thumbnail');
                $thumbName = time().'_thumb_'.$thumb->getClientOriginalName();

                $thumb->move($thumbPath, $thumbName);

                $validated['thumbnail'] = '/thumbnails/blog/'.$thumbName;
            }

            // VIDEO CLEANUP IF NEW VIDEO URL PROVIDED
            if (! empty($validated['video_url']) && $blogPost->video_url) {
                if (file_exists(public_path($blogPost->video_url))) {
                    unlink(public_path($blogPost->video_url));
                }
            }

            $blogPost->update($validated);

            return response()->json($blogPost);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update blog post',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete blog post
     */
    public function destroy(BlogPost $blogPost)
    {
        // Delete image if exists
        if ($blogPost->image && file_exists(public_path($blogPost->image))) {
            unlink(public_path($blogPost->image));
        }
        // delete video if exists
        if ($blogPost->video_url && file_exists(public_path($blogPost->video_url))) {
            unlink(public_path($blogPost->video_url));
        }

        // Delete thumbnail if exists
        if ($blogPost->thumbnail && file_exists(public_path($blogPost->thumbnail))) {
            unlink(public_path($blogPost->thumbnail));
        }

        // Delete DB record
        $blogPost->delete();

        return response()->json(['message' => 'Blog post deleted successfully']);
    }

    // uploading video in chunks
    public function uploadVideoChunk(Request $request)
    {
        try {
            $validated = $request->validate([
                'upload_id' => 'required|string',
                'chunk_index' => 'required|integer|min:0',
                'total_chunks' => 'required|integer|min:1',
                'file' => 'required|file|max:102400',
            ]);

            $uploadId = trim($validated['upload_id']);
            $chunkIndex = (int) $validated['chunk_index'];
            $totalChunks = (int) $validated['total_chunks'];

            $file = $request->file('file');

            if (! $file || ! $file->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid chunk file',
                ], 422);
            }

            // CHUNK STORAGE
            $chunkDir = storage_path("app/video_chunk/{$uploadId}");

            if (! is_dir($chunkDir)) {
                mkdir($chunkDir, 0777, true);
            }

            $chunkFilePath = $chunkDir."/chunk_{$chunkIndex}";

            // SAFE WRITE (no getRealPath issues)
            $sourcePath = $file->getPathname();

            if (! file_exists($sourcePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Temporary file missing',
                ], 422);
            }

            $input = fopen($sourcePath, 'rb');
            $output = fopen($chunkFilePath, 'wb');

            if (! $input || ! $output) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to write chunk',
                ], 500);
            }

            stream_copy_to_stream($input, $output);

            fclose($input);
            fclose($output);

            // CHECK IF ALL CHUNKS ARE READY
            $missing = [];

            for ($i = 0; $i < $totalChunks; $i++) {
                if (! file_exists($chunkDir."/chunk_{$i}")) {
                    $missing[] = $i;
                }
            }

            if (! empty($missing)) {
                return response()->json([
                    'completed' => false,
                    'message' => 'Waiting for remaining chunks...',
                    'missing' => $missing,
                ]);
            }

            // FINAL OUTPUT FILE
            $finalFolder = public_path('videos/blog');

            if (! is_dir($finalFolder)) {
                mkdir($finalFolder, 0777, true);
            }

            $finalFilename = time().'_'.$uploadId.'.mp4';
            $finalPath = $finalFolder.'/'.$finalFilename;

            $output = fopen($finalPath, 'wb');

            if (! $output) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot create final video file',
                ], 500);
            }

            // MERGE CHUNKS
            for ($i = 0; $i < $totalChunks; $i++) {
                $chunkPath = $chunkDir."/chunk_{$i}";

                $input = fopen($chunkPath, 'rb');

                if (! $input) {
                    fclose($output);

                    return response()->json([
                        'success' => false,
                        'message' => "Cannot read chunk {$i}",
                    ], 422);
                }

                stream_copy_to_stream($input, $output);
                fclose($input);
            }

            fclose($output);

            // CLEANUP
            foreach (glob($chunkDir.'/*') as $filePath) {
                @unlink($filePath);
            }

            @rmdir($chunkDir);

            return response()->json([
                'completed' => true,
                'video_url' => '/videos/blog/'.$finalFilename,
                'message' => 'Video upload completed!',
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
