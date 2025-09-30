<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Feedback;
use App\Models\FeedbackTranslation;
use App\Models\Language;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FeedbacksController extends Controller
{

    public $languages;

    public function __construct()
    {
        $this->languages = Language::get();
    }
    public function index()
    {
        $feedbacks = Feedback::with('translations')->paginate(10);
        return view('dashboard.feedbacks.index', compact('feedbacks'));
    }

    public function create()
    {
        $feedback = new Feedback();
        $feedbackTranslations = [];
        $languages = $this->languages;
        return view('dashboard.feedbacks.create', compact('feedback', 'feedbackTranslations', 'languages'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'order' => 'required|integer',
            'image' => 'nullable', // optional file
            'feedbackTranslations.*.feedback' => 'required|string',
            'feedbackTranslations.*.name' => 'required|string',
            'feedbackTranslations.*.major' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            // حفظ البيانات
            $feedback = Feedback::create($request->all());


            // حفظ الترجمات
            foreach ($request->feedbackTranslations as $translation) {
                FeedbackTranslation::create(
                    [
                        'feedback_id' => $feedback->id,
                        'locale' => $translation['locale'],
                        'feedback' => $translation['feedback'],
                        'name' => $translation['name'],
                        'major' => $translation['major']
                    ]
                );
            }

            DB::commit();

            return redirect()->route('dashboard.feedbacks.index')->with('success', 'تم إنشاء القالب بنجاح.');
        } catch (\Exception $e) {
            DB::rollBack();
            dd($e);
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function edit($id)
    {
        $feedback = Feedback::with('translations')->findOrFail($id);
        $feedbackTranslations = [];
        foreach ($this->languages as $lang) {
            $trans = $feedback->translations->firstWhere('locale', $lang->code);
            $feedbackTranslations[$lang->code] = [
                'locale' => $lang->code,
                'feedback' => $trans?->feedback ?? '',
                'name' => $trans?->name ?? '',
                'major' => $trans?->major ?? '',
            ];
        }
        $languages = $this->languages;
        return view('dashboard.feedbacks.edit', compact('feedback', 'feedbackTranslations', 'languages'));
    }

    public function update(Request $request, $id)
    {
        $feedback = Feedback::findOrFail($id);
        $request->validate([
            'order' => 'required|integer',
            'image' => 'nullable', // optional file
            'feedbackTranslations.*.feedback' => 'required|string',
            'feedbackTranslations.*.name' => 'required|string',
            'feedbackTranslations.*.major' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            $feedback->update($request->all());

            // إعادة إدخال الترجمات
            foreach ($request->feedbackTranslations as $translation) {
                FeedbackTranslation::updateOrCreate(
                    ['feedback_id' => $feedback->id, 'locale' => $translation['locale']],
                    [
                        'feedback' => $translation['feedback'],
                        'name' => $translation['name'],
                        'major' => $translation['major']
                    ]
                );
            }

            DB::commit();

            return redirect()->route('dashboard.feedbacks.index')->with('success', 'تم تعديل القالب بنجاح.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }


    public function destroy($id)
    {
        $feedback = Feedback::findOrFail($id);

        $feedback->delete();

        return redirect()->route('dashboard.feedbacks.index')->with('success', 'تم حذف القالب بنجاح.');
    }
}
