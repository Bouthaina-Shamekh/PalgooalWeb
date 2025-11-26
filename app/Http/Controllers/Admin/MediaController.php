<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\MediaResource;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    /**
     * عرض قائمة الوسائط (مع دعم الفلترة والبحث والـ pagination).
     *
     * Query params:
     * - type=image|video|document|other
     * - search=term أو q=term
     * - per_page=40
     */
    public function index(Request $request)
    {
        // لو الطلب عادي من المتصفح → رجّع صفحة Blade
        if (! $request->wantsJson()) {
            return view('dashboard.media');
        }

        $perPage = (int) $request->get('per_page', 40);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 40;

        $query = Media::query()->latest();

        // فلترة حسب نوع الميديا (image/video/document/other)
        if ($type = $request->get('type')) {
            $query->where('file_type', $type);
            // أو لو تحب تستخدم الـ scope:
            // $query->ofType($type);
        }

        // بحث: ندعم search و q للتوافق مع الواجهة الحالية/القديمة
        $term = $request->get('search') ?? $request->get('q');

        if ($term) {
            $query->where(function ($q) use ($term) {
                $q->where('file_original_name', 'LIKE', "%{$term}%")
                    ->orWhere('file_name', 'LIKE', "%{$term}%")
                    ->orWhere('title', 'LIKE', "%{$term}%")
                    ->orWhere('caption', 'LIKE', "%{$term}%")
                    ->orWhere('description', 'LIKE', "%{$term}%");
            });

            // أو لو حاب، ممكن تستخدم scopeSearch في الـ Model:
            // $query->search($term);
        }

        $paginator = $query->paginate($perPage);

        // نحافظ على شكل الـ pagination كما هو،
        // لكن نمرر كل عنصر على MediaResource
        $paginator = $paginator->through(fn($media) => new MediaResource($media));

        return response()->json($paginator);
    }

    /**
     * رفع وسائط:
     * - يدعم "image" (مفرد) أو "files[]" (متعدد)
     * - الحد الأقصى 10MB لكل ملف
     * - أنواع: jpeg, jpg, png, gif, webp, svg
     */
    public function store(Request $request)
    {
        // رفع متعدد: files[]
        if ($request->hasFile('files')) {
            $request->validate([
                'files'   => 'required|array',
                'files.*' => 'file|mimes:jpeg,jpg,png,gif,webp,svg|max:10240|mimetypes:image/jpeg,image/png,image/gif,image/webp,image/svg+xml',
            ]);

            $uploaded = [];
            foreach ($request->file('files') as $file) {
                $uploaded[] = $this->saveMediaFile($file);
            }

            return response()->json([
                'uploaded' => MediaResource::collection(collect($uploaded)),
            ], 201);
        }

        // رفع مفرد: image
        if ($request->hasFile('image')) {
            $request->validate([
                'image' => 'required|file|mimes:jpeg,jpg,png,gif,webp,svg|max:10240|mimetypes:image/jpeg,image/png,image/gif,image/webp,image/svg+xml',
            ]);

            $media = $this->saveMediaFile($request->file('image'));

            return response()->json(new MediaResource($media), 201);
        }

        // لا يوجد ملفات مرسلة
        return response()->json([
            'message' => 'حقل الصورة مفقود. أرسل "image" (ملف واحد) أو "files[]" (عدة ملفات).',
        ], 422);
    }

    /**
     * عرض عنصر وسائط واحد
     */
    public function show($id)
    {
        $media = Media::findOrFail($id);

        return response()->json(new MediaResource($media));
    }

    /**
     * (اختياري) نفس show للحفاظ على التوافق مع أي كود قديم
     */
    public function edit($id)
    {
        $media = Media::findOrFail($id);

        return response()->json(new MediaResource($media));
    }

    /**
     * تحديث الحقول الوصفية للوسائط
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
     * حذف ملف وسائط + سجله
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
     * دالة مساعدة لحفظ ملف وإنشاء السجل في قاعدة البيانات
     */
    private function saveMediaFile(UploadedFile $file): Media
    {
        $disk = 'public';

        $now  = now();
        $dir  = 'media/' . $now->format('Y') . '/' . $now->format('m');

        $originalName = $file->getClientOriginalName();
        $extension    = strtolower($file->getClientOriginalExtension());
        $mimeType     = $file->getMimeType();

        $hashedName = uniqid('', true) . '.' . $extension;

        $path = Storage::disk($disk)->putFileAs($dir, $file, $hashedName);

        $width  = null;
        $height = null;

        if (str_starts_with((string) $mimeType, 'image/')) {
            try {
                $imageSize = getimagesize($file->getPathname());
                if ($imageSize) {
                    $width  = $imageSize[0] ?? null;
                    $height = $imageSize[1] ?? null;
                }
            } catch (\Throwable $e) {
                // تجاهل الخطأ
            }
        }

        $fileType = $this->detectFileType($mimeType, $extension);

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
     * تصنيف نوع الميديا بناءً على mime/extension
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

        $documentExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];
        if (in_array($extension, $documentExtensions, true)) {
            return 'document';
        }

        return 'other';
    }
}
