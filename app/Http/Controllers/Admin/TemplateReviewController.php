<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TemplateReview;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class TemplateReviewController extends Controller
{
    /**
     * عرض قائمة المراجعات مع البحث والفلاتر
     */
    public function index(Request $request)
    {
        $text = trim((string) $request->input('q', ''));

        // null = بدون فلتر، otherwise true/false
        $approved = $request->filled('approved') && $request->input('approved') !== ''
            ? $request->boolean('approved')
            : null;

        // لو المستخدم ما اختار تقييم، نخليه null عشان ما نفلتر
        $rating = $request->filled('rating')
            ? (int) $request->input('rating')
            : null;

        $reviews = TemplateReview::query()
            ->with([
                // 🟢 تحميل بيانات الترجمة على القالب
                'template.translations:id,template_id,slug,locale',
                'client:id,first_name,last_name,email',
                'user:id,name,email',
            ])
            ->when($text !== '', function ($query) use ($text) {
                $query->where(function ($q) use ($text) {
                    $q->where('comment', 'like', "%{$text}%")
                        ->orWhere('author_name', 'like', "%{$text}%")
                        ->orWhere('author_email', 'like', "%{$text}%")
                        ->orWhereHas('client', function ($c) use ($text) {
                            $c->where('first_name', 'like', "%{$text}%")
                                ->orWhere('last_name', 'like', "%{$text}%")
                                ->orWhere('email', 'like', "%{$text}%");
                        })
                        ->orWhereHas('user', function ($u) use ($text) {
                            $u->where('name', 'like', "%{$text}%")
                                ->orWhere('email', 'like', "%{$text}%");
                        });
                });
            })
            ->when(!is_null($approved), fn($q) => $q->where('approved', $approved))
            ->when(!is_null($rating), fn($q) => $q->where('rating', $rating))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('dashboard.templates.reviews', compact('reviews'));
    }

    /**
     * اعتماد مراجعة مفردة
     */
    public function approve(TemplateReview $review)
    {
        $review->approved = true;
        $review->save();

        return back()->with('success', 'تم اعتماد المراجعة بنجاح.');
    }

    /**
     * إلغاء اعتماد مراجعة مفردة
     */
    public function reject(TemplateReview $review)
    {
        $review->approved = false;
        $review->save();

        return back()->with('success', 'تم إلغاء اعتماد المراجعة.');
    }

    /**
     * حذف مراجعة مفردة
     */
    public function destroy(TemplateReview $review)
    {
        // يدعم SoftDeletes إن كانت مفعّلة على الموديل
        $review->delete();

        return back()->with('success', 'تم حذف المراجعة بنجاح.');
    }

    /**
     * إجراءات مجمّعة: اعتماد/رفض/حذف
     */
    public function bulk(Request $request)
    {
        $data = $request->validate([
            'ids'    => ['required', 'array', 'min:1'],
            'ids.*'  => ['integer', 'distinct'],
            'action' => ['required', 'in:approve,reject,delete'],
        ], [
            'ids.required' => 'يرجى اختيار عنصر واحد على الأقل.',
        ]);

        $ids    = $data['ids'];
        $action = $data['action'];

        $affected = 0;

        DB::transaction(function () use ($ids, $action, &$affected) {
            $q = TemplateReview::whereIn('id', $ids);

            if ($action === 'approve') {
                $affected = $q->update(['approved' => 1]);
            } elseif ($action === 'reject') {
                $affected = $q->update(['approved' => 0]);
            } else {
                // delete (يدعم SoftDeletes إن وُجدت)
                $affected = $q->delete();
            }
        });

        $messages = [
            'approve' => "تم اعتماد {$affected} مراجعة.",
            'reject'  => "تم رفض {$affected} مراجعة.",
            'delete'  => "تم حذف {$affected} مراجعة.",
        ];

        return back()->with('success', $messages[$action]);
    }
}

