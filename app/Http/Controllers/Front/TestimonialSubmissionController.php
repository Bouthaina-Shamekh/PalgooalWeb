<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Testimonial;
use App\Models\TestimonialTranslation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TestimonialSubmissionController extends Controller
{
    public function create()
    {
        $languages = Language::where('is_active', true)->get();

        abort_if($languages->isEmpty(), 404);

        return view('tamplate.testimonials.submit', [
            'languages' => $languages,
        ]);
    }

    public function store(Request $request)
    {
        $languages = Language::where('is_active', true)->get();
        $localeCodes = $languages->pluck('code')->filter()->values()->all();

        abort_if(empty($localeCodes), 404);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'major' => 'required|string|max:255',
            'feedback' => 'required|string',
            'star' => 'required|integer|min:1|max:5',
            'language' => ['required', 'string', Rule::in($localeCodes)],
            'image' => 'nullable|image|max:2048',
        ]);

        $order = (Testimonial::max('order') ?? 0) + 1;

        DB::beginTransaction();

        try {
            $testimonial = Testimonial::create([
                'order' => $order,
                'star' => $validated['star'],
                'is_approved' => false,
                'image' => $request->hasFile('image')
                    ? $request->file('image')->store('testimonials', 'public')
                    : null,
            ]);

            TestimonialTranslation::create([
                'feedback_id' => $testimonial->id,
                'locale' => $validated['language'],
                'feedback' => $validated['feedback'],
                'name' => $validated['name'],
                'major' => $validated['major'],
            ]);

            DB::commit();

            return redirect()
                ->route('testimonials.submit')
                ->with('success', __('شكراً لك! تم استلام تقييمك وسنقوم بمراجعته قبل نشره.'));
        } catch (\Throwable $th) {
            DB::rollBack();

            return back()->withErrors([
                'error' => __('حدث خطأ غير متوقع، يرجى المحاولة مرة أخرى.'),
            ])->withInput();
        }
    }
}

