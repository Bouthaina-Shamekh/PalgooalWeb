<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\TemplateReview;
use Illuminate\Http\Request;

class TemplateReviewController extends Controller
{
    public function index(Request $request)
    {
        $q = TemplateReview::query()->with(['template','client','user'])
            ->when($request->filled('approved'), function ($query) use ($request) {
                $val = $request->boolean('approved');
                $query->where('approved', $val);
            })
            ->latest();

        $reviews = $q->paginate(20);

        return view('dashboard.templates.reviews', compact('reviews'));
    }

    public function approve(TemplateReview $review)
    {
        $review->approved = true;
        $review->save();
        return back()->with('success', 'تم اعتماد المراجعة بنجاح.');
    }

    public function reject(TemplateReview $review)
    {
        $review->approved = false;
        $review->save();
        return back()->with('success', 'تم إلغاء اعتماد المراجعة.');
    }

    public function destroy(TemplateReview $review)
    {
        $review->delete();
        return back()->with('success', 'تم حذف المراجعة بنجاح.');
    }
}