<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\MediaResource;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Class MediaController
 *
 * Centralized controller for managing media files in the dashboard.
 * Responsibilities:
 * - List media items with filters and pagination (API + Blade view)
 * - Upload media (single & multiple)
 * - Show/update media metadata
 * - Delete media from storage and database
 */
class MediaController extends Controller
{
    /**
     * Display a list of media items (with filtering, search, and pagination).
     *
     * Behavior:
     * - If the request does NOT expect JSON -> returns the Blade view (media page).
     * - If the request expects JSON (AJAX/API) -> returns paginated JSON.
     *
     * Query params:
     * - type=image|video|document|other (filter by file_type)
     * - search=term or q=term       (simple text search)
     * - per_page=40                 (items per page, clamped between 1 and 100)
     */
    public function index(Request $request)
    {
        // If it's a normal browser request (HTML), return the media page view.
        // The front-end (JS) will then consume the JSON API from the same route.
        if (! $request->wantsJson()) {
            return view('dashboard.media');
        }

        // Sanitize per_page: default 40, maximum 100.
        $perPage = (int) $request->get('per_page', 40);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 40;

        // Base query: latest media items first
        $query = Media::query()->latest();

        // Optional type filter: image / video / document / other
        if ($type = $request->get('type')) {
            $query->where('file_type', $type);
            // Alternatively, we could use the model scope:
            // $query->ofType($type);
        }

        // Simple text search: supports both "search" and legacy "q" parameter
        $term = $request->get('search') ?? $request->get('q');

        if ($term) {
            $query->where(function ($q) use ($term) {
                $q->where('file_original_name', 'LIKE', "%{$term}%")
                    ->orWhere('file_name', 'LIKE', "%{$term}%")
                    ->orWhere('title', 'LIKE', "%{$term}%")
                    ->orWhere('caption', 'LIKE', "%{$term}%")
                    ->orWhere('description', 'LIKE', "%{$term}%");
            });

            // Or via the model scope (if you prefer a cleaner controller):
            // $query->search($term);
        }

        // Standard Laravel paginator
        $paginator = $query->paginate($perPage);

        // Transform each media item into a MediaResource while keeping pagination meta
        $paginator = $paginator->through(fn($media) => new MediaResource($media));

        return response()->json($paginator);
    }

    /**
     * Handle media upload requests.
     *
     * Supported payloads:
     * - Multiple files: "files[]" (e.g. from drag & drop or multi-select input)
     * - Single file: "image"
     *
     * Constraints (per file):
     * - Max size: 10MB
     * - Allowed extensions: jpeg, jpg, png, gif, webp, svg
     * - Allowed MIME types: image/jpeg, image/png, image/gif, image/webp, image/svg+xml
     */
    public function store(Request $request)
    {
        // Case 1: Multiple file upload using "files[]"
        if ($request->hasFile('files')) {
            $request->validate([
                'files'   => 'required|array',
                'files.*' => 'file|mimes:jpeg,jpg,png,gif,webp,svg|max:10240|mimetypes:image/jpeg,image/png,image/gif,image/webp,image/svg+xml',
            ]);

            $uploaded = [];
            foreach ($request->file('files') as $file) {
                $uploaded[] = $this->saveMediaFile($file);
            }

            // Wrap uploaded models in resources for consistent API shape
            return response()->json([
                'uploaded' => MediaResource::collection(collect($uploaded)),
            ], 201);
        }

        // Case 2: Single file upload using "image"
        if ($request->hasFile('image')) {
            $request->validate([
                'image' => 'required|file|mimes:jpeg,jpg,png,gif,webp,svg|max:10240|mimetypes:image/jpeg,image/png,image/gif,image/webp,image/svg+xml',
            ]);

            $media = $this->saveMediaFile($request->file('image'));

            return response()->json(new MediaResource($media), 201);
        }

        // No files provided → return 422 with an instructional error message
        return response()->json([
            'message' => 'حقل الصورة مفقود. أرسل "image" (ملف واحد) أو "files[]" (عدة ملفات).',
        ], 422);
    }

    /**
     * Show a single media item as JSON.
     *
     * Useful for editing metadata or previewing additional details in the UI.
     */
    public function show($id)
    {
        $media = Media::findOrFail($id);

        return response()->json(new MediaResource($media));
    }

    /**
     * Edit endpoint (optional).
     *
     * Kept for compatibility with any legacy code that might be calling /media/{id}/edit.
     * It simply proxies to the same JSON response as show().
     */
    public function edit($id)
    {
        $media = Media::findOrFail($id);

        return response()->json(new MediaResource($media));
    }

    /**
     * Update media metadata (does NOT replace the underlying file).
     *
     * Editable fields:
     * - file_original_name
     * - alt
     * - title
     * - caption
     * - description
     */
    public function update(Request $request, $id)
    {
        $media = Media::findOrFail($id);

        $request->validate([
            'file_original_name' => 'nullable|string|max:255',
            'alt'         => 'nullable|string|max:255',
            'title'       => 'nullable|string|max:255',
            'caption'     => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $data = $request->only([
            'file_original_name',
            'alt',
            'title',
            'caption',
            'description',
        ]);

        $media->update($data);

        return response()->json([
            'message' => 'تم التحديث بنجاح',
            'media'   => new MediaResource($media),
        ]);
    }

    /**
     * Delete a media file from storage AND its database record.
     *
     * Notes:
     * - Uses the disk column (default: "public")
     * - Silently ignores missing files in storage (Storage::delete is safe)
     */
    public function destroy($id)
    {
        $media = Media::findOrFail($id);

        if ($media->file_path) {
            $disk = $media->disk ?: 'public';
            Storage::disk($disk)->delete($media->file_path);
        }

        $media->delete();

        return response()->json(['message' => 'تم الحذف']);
    }

    /**
     * Helper method to store an uploaded file and create the corresponding
     * Media record in the database.
     *
     * Steps:
     * - Determine storage disk and directory
     * - Generate a unique file name
     * - Store the file via Storage
     * - Optionally read image dimensions (width/height)
     * - Detect logical file type (image/video/audio/document/other)
     * - Persist and return the Media model instance
     */
    private function saveMediaFile(UploadedFile $file): Media
    {
        $disk = 'public';

        // Folder structure: media/YYYY/MM
        $now  = now();
        $dir  = 'media/' . $now->format('Y') . '/' . $now->format('m');

        $originalName = $file->getClientOriginalName();
        $extension    = strtolower($file->getClientOriginalExtension());
        $mimeType     = $file->getMimeType();

        // Unique file name to avoid collisions
        $hashedName = uniqid('', true) . '.' . $extension;

        // Store the file and get the relative path
        $path = Storage::disk($disk)->putFileAs($dir, $file, $hashedName);

        $width  = null;
        $height = null;

        // If it’s an image, try to read its dimensions
        if (str_starts_with((string) $mimeType, 'image/')) {
            try {
                $imageSize = getimagesize($file->getPathname());
                if ($imageSize) {
                    $width  = $imageSize[0] ?? null;
                    $height = $imageSize[1] ?? null;
                }
            } catch (\Throwable $e) {
                // Silently ignore any error while reading dimensions
            }
        }

        // Classify into a logical file type
        $fileType = $this->detectFileType($mimeType, $extension);

        // Create and return the Media record
        return Media::create([
            'file_name'          => $hashedName,
            'file_original_name' => $originalName,
            'file_path'          => $path,
            'file_extension'     => $extension,
            'mime_type'          => $mimeType,
            'size'               => $file->getSize(),
            'file_type'          => $fileType,
            'disk'               => $disk,
            'width'              => $width,
            'height'             => $height,
            'uploader_id'        => Auth::id(),
        ]);
    }

    /**
     * Determine the logical file_type value based on MIME type and extension.
     *
     * Returns one of:
     * - image
     * - video
     * - audio
     * - document
     * - other
     */
    private function detectFileType(?string $mimeType, ?string $extension): string
    {
        $mimeType  = strtolower((string) $mimeType);
        $extension = strtolower((string) $extension);

        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }

        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }

        if (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        }

        // Basic document extensions grouping
        $documentExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];
        if (in_array($extension, $documentExtensions, true)) {
            return 'document';
        }

        return 'other';
    }
}
