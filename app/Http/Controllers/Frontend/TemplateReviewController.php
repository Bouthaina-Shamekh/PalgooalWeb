<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Template;
use App\Models\TemplateReview;
use Illuminate\Http\Request;

class TemplateReviewController extends Controller
{
    /**
     * تخزين مراجعة جديدة لقالب معيّن.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Template      $template  (Route Model Binding على {template})
     */
    public function store(Request $request, int $template_id)
    {
        // نجلب القالب بالـ ID
        $template = Template::findOrFail($template_id);
        // المستخدم ضيف (لا أدمن ولا عميل)
        $isGuest = !auth()->check() && !auth('client')->check();
       
        // قواعد التحقق الأساسية
        $rules = [
            'rating'  => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'min:5', 'max:2000'],
        ];

        // لو ضيف لازم يكتب الاسم والإيميل
        if ($isGuest) {
            $rules['author_name']  = ['required', 'string', 'max:191'];
            $rules['author_email'] = ['required', 'email', 'max:191'];
        }

        $data = $request->validate($rules);

        // إنشاء المراجعة وربطها بالقالب
        $review = new TemplateReview();
        $review->template_id = $template->id;
        $review->rating      = (int) $data['rating'];
        $review->comment     = $data['comment'];
        $review->approved    = false; // تظل قيد المراجعة حتى تعتمدها من لوحة التحكم

        // ربط الهوية حسب نوع الدخول
        if (auth()->check()) {
            // مستخدم من نظام الإدارة (users)
            $review->user_id = auth()->id();
        } elseif (auth('client')->check()) {
            // عميل من جدول clients
            $review->client_id = auth('client')->id();
        } else {
            // ضيف (بدون تسجيل دخول)
            $review->author_name  = $data['author_name'];
            $review->author_email = $data['author_email'];
        }

        $review->save();

        return back()->with('success', 'تم استلام مراجعتك، وستظهر بعد المراجعة والاعتماد.');
    }
}
