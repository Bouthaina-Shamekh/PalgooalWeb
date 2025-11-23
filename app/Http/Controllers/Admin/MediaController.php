<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
     * Query params مقترحة:
     * - type=image|video|document|other
     * - q=term (search)
     * - per_page=40
     */
    public function index(Request $request)
    {
        $query = Media::query()->latest();

        // فلترة حسب نوع الميديا (image/video/document/other)
        if ($type = $request->get('type')) {
            $query->where('file_type', $type);
        }

        // بحث بسيط بالاسم الأصلي أو العنوان أو الكابشن
        if ($term = $request->get('q')) {
            $query->where(function ($q) use ($term) {
                $q->where('file_original_name', 'LIKE', "%{$term}%")
                    ->orWhere('title', 'LIKE', "%{$term}%")
                    ->orWhere('caption', 'LIKE', "%{$term}%");
            });
        }

        $perPage = (int) $request->get('per_page', 40);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 40;

        $media = $query->paginate($perPage);

        return response()->json($media);
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

            return response()->json(['uploaded' => $uploaded], 201);
        }

        // رفع مفرد: image
        if ($request->hasFile('image')) {
            $request->validate([
                'image' => 'required|file|mimes:jpeg,jpg,png,gif,webp,svg|max:10240|mimetypes:image/jpeg,image/png,image/gif,image/webp,image/svg+xml',
            ]);

            $media = $this->saveMediaFile($request->file('image'));

            return response()->json($media, 201);
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

        return response()->json($media);
    }

    /**
     * (اختياري) نفس show للحفاظ على التوافق مع أي كود قديم
     */
    public function edit($id)
    {
        $media = Media::findOrFail($id);

        return response()->json($media);
    }

    /**
     * تحديث الحقول الوصفية للوسائط
     */
    public function update(Request $request, $id)
    {
        $media = Media::findOrFail($id);

        $request->validate([
            // لو حاب تسمح للمستخدم يعدّل الاسم الأصلي المعروض
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
            'media'   => $media,
        ]);
    }

    /**
     * حذف ملف وسائط + سجله
     */
    public function destroy($id)
    {
        $media = Media::findOrFail($id);

        // حذف الملف من التخزين إن وُجد
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

        // نبني مسار منظم: media/YYYY/MM
        $now  = now();
        $dir  = 'media/' . $now->format('Y') . '/' . $now->format('m');

        // الامتداد والاسم الأصلي
        $originalName = $file->getClientOriginalName();
        $extension    = strtolower($file->getClientOriginalExtension());
        $mimeType     = $file->getMimeType();

        // اسم ملف مُهشَّر لتفادي التعارض
        $hashedName = uniqid('', true) . '.' . $extension;

        // نخزّن الملف باستخدام putFileAs لضبط المسار والاسم
        $path = Storage::disk($disk)->putFileAs($dir, $file, $hashedName);

        // محاولة قراءة أبعاد الصورة (إن كانت صورة)
        $width  = null;
        $height = null;

        if (str_starts_with($mimeType, 'image/')) {
            try {
                $imageSize = getimagesize($file->getPathname());
                if ($imageSize) {
                    $width  = $imageSize[0] ?? null;
                    $height = $imageSize[1] ?? null;
                }
            } catch (\Throwable $e) {
                // تجاهل أي خطأ في قراءة الأبعاد
            }
        }

        // تصنيف نوع الميديا (image/document/other)
        $fileType = $this->detectFileType($mimeType, $extension);

        // إنشاء السجل في قاعدة البيانات
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

        // بعض الامتدادات الشائعة للوثائق
        $documentExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];
        if (in_array($extension, $documentExtensions, true)) {
            return 'document';
        }

        return 'other';
    }
}
