<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use App\Models\Media;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class MediaController extends Controller
{
    /**
     * إرجاع جميع الوسائط (كمصفوفة) — ملائم للواجهة الحالية.
     * (يمكن لاحقًا تحويلها إلى paginate بدون كسر الواجهة)
     */
    public function index()
    {
        $media = Media::latest()->get();
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
                'files'    => 'required|array',
                'files.*'  => 'file|mimes:jpeg,jpg,png,gif,webp,svg|max:10240|mimetypes:image/jpeg,image/png,image/gif,image/webp,image/svg+xml',
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
     * (اختياري) نفس show للحفاظ على التوافق
     */
    public function edit($id)
    {
        $media = Media::findOrFail($id);
        return response()->json($media);
    }

    /**
     * تحديث حقول وصفية للوسائط
     */
    public function update(Request $request, $id)
    {
        $media = Media::findOrFail($id);

        $request->validate([
            'name'        => 'nullable|string|max:255',
            'alt'         => 'nullable|string|max:255',
            'title'       => 'nullable|string|max:255',
            'caption'     => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $media->update($request->only([
            'name',
            'alt',
            'title',
            'caption',
            'description',
        ]));

        return response()->json(['message' => 'تم التحديث بنجاح']);
    }

    /**
     * حذف ملف وسائط + سجله
     */
    public function destroy($id)
    {
        $media = Media::findOrFail($id);

        // حذف الملف من التخزين إن وُجد
        if ($media->file_path) {
            Storage::disk('public')->delete($media->file_path);
        }

        $media->delete();

        return response()->json(['message' => 'تم الحذف']);
    }

    /**
     * دالة مساعدة لحفظ ملف وإنشاء السجل في قاعدة البيانات
     */
    private function saveMediaFile(UploadedFile $file): Media
    {
        // يحفظ في storage/app/public/uploads/media
        $path = $file->store('uploads/media', 'public');

        return Media::create([
            'name'        => $file->getClientOriginalName(),
            'file_path'   => $path,
            'mime_type'   => $file->getMimeType(),
            'size'        => $file->getSize(),
            'uploader_id' => Auth::id(), // أفضل من Auth::user()->id لتفادي الأخطاء إن لم يُسجّل المستخدم
        ]);
    }
}

