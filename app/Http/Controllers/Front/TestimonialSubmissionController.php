<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Media;
use App\Models\Testimonial;
use App\Models\TestimonialTranslation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TestimonialSubmissionController extends Controller
{
    public function create()
    {
        $languages = Language::where('is_active', true)->get();

        abort_if($languages->isEmpty(), 404);

        return view('front.testimonials.submit', [
            'languages' => $languages,
        ]);
    }

    public function store(Request $request)
    {
        $languages   = Language::where('is_active', true)->get();
        $localeCodes = $languages->pluck('code')->filter()->values()->all();

        abort_if(empty($localeCodes), 404);

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'major'    => 'required|string|max:255',
            'feedback' => 'required|string',
            'star'     => 'required|integer|min:1|max:5',
            'language' => ['required', 'string', Rule::in($localeCodes)],
            'image'    => 'nullable|image|max:2048',
        ]);

        $order = (Testimonial::max('order') ?? 0) + 1;

        DB::beginTransaction();

        try {
            // P3 fix: store the uploaded file as a Media record and use image_id FK.
            // Previously the code wrote 'image' => path which is not a column on feedbacks.
            $imageId = null;

            if ($request->hasFile('image')) {
                $file      = $request->file('image');
                $path      = $file->store('testimonials', 'public');
                $extension = $file->getClientOriginalExtension();

                $media = Media::create([
                    'file_name'          => basename($path),
                    'file_original_name' => $file->getClientOriginalName(),
                    'file_path'          => $path,
                    'file_extension'     => $extension,
                    'mime_type'          => $file->getMimeType(),
                    'size'               => $file->getSize(),
                    'file_type'          => 'image',
                    'disk'               => 'public',
                ]);

                $imageId = $media->id;
            }

            $testimonial = Testimonial::create([
                'order'       => $order,
                'star'        => $validated['star'],
                'is_approved' => false,
                'image_id'    => $imageId,
            ]);

            TestimonialTranslation::create([
                'feedback_id' => $testimonial->id,
                'locale'      => $validated['language'],
                'feedback'    => $validated['feedback'],
                'name'        => $validated['name'],
                'major'       => $validated['major'],
            ]);

            DB::commit();

            return redirect()
                ->route('testimonials.submit')
                ->with('success', __('شكراً لك! تم استلام تقييمك وسنقوم بمراجعته قبل نشره.'));

        } catch (\Throwable $th) {
            DB::rollBack();

            // Clean up the uploaded file if the DB transaction failed
            if (isset($path) && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }

            return back()->withErrors([
                'error' => __('حدث خطأ غير متوقع، يرجى المحاولة مرة أخرى.'),
            ])->withInput();
        }
    }
}
