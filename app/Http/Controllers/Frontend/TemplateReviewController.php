<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Template;
use App\Models\TemplateReview;
use Illuminate\Http\Request;


class TemplateReviewController extends Controller
{
    public function store(Request $request, int $template)
    {
        // نجلب القالب بالـ ID
        $template = Template::findOrFail($template);

        // نحدد هل المستخدم ضيف (لا أدمن ولا عميل)
        $isGuest = !auth()->check() && !auth('client')->check();

        // قواعد التحقق
        $rules = [
            'rating'  => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'min:5', 'max:2000'],
        ];
        if ($isGuest) {
            $rules['author_name']  = ['required', 'string', 'max:191'];
            $rules['author_email'] = ['required', 'email', 'max:191'];
        }

        $data = $request->validate($rules);

        // إنشاء المراجعة
        $review = new TemplateReview();
        $review->template_id = $template->id;
        $review->rating      = (int) $data['rating'];
        $review->comment     = $data['comment'];
        $review->approved    = false; // تظل قيد المراجعة حتى تعتمدها من لوحة التحكم

        // ربط الهوية حسب نوع الدخول
        if (auth()->check()) {
            $review->user_id = auth()->id(); // أدمن/يوزر النظام
        } elseif (auth('client')->check()) {
            $review->client_id = auth('client')->id(); // عميلك
        } else {
            // ضيف
            $review->author_name  = $data['author_name'];
            $review->author_email = $data['author_email'];
        }

        $review->save();

        return back()->with('success', 'تم استلام مراجعتك، وستظهر بعد المراجعة والاعتماد.');
    }
}
