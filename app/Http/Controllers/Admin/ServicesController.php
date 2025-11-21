<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Service;
use App\Models\ServiceTranslation;
use App\Models\Language;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ServicesController extends Controller
{
    public $languages;

    public function __construct()
    {
        $this->languages = Language::get();
    }
    public function index()
    {
        $services = Service::with('translations')->paginate(10);
        return view('dashboard.services.index', compact('services'));
    }

    public function create()
    {
        $service = new Service();
        $serviceTranslations = [];
        $languages = $this->languages;
        return view('dashboard.services.create', compact('service', 'serviceTranslations', 'languages'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'order' => 'required|integer',
            'icon' => 'nullable',
            'url' => 'nullable',
            'serviceTranslations.*.title' => 'required|string',
            'serviceTranslations.*.description' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            // حفظ البيانات
            $service = Service::create($request->all());


            // حفظ الترجمات
            foreach ($request->serviceTranslations as $translation) {
                ServiceTranslation::create(
                    [
                        'service_id' => $service->id,
                        'locale' => $translation['locale'],
                        'title' => $translation['title'],
                        'description' => $translation['description']
                    ]
                );
            }

            DB::commit();

            return redirect()->route('dashboard.services.index')->with('success', 'تم إنشاء القالب بنجاح.');
        } catch (\Exception $e) {
            DB::rollBack();
            dd($e);
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function edit($id)
    {
        $service = Service::with('translations')->findOrFail($id);
        $serviceTranslations = [];
        foreach ($this->languages as $lang) {
            $trans = $service->translations->firstWhere('locale', $lang->code);
            $serviceTranslations[$lang->code] = [
                'locale' => $lang->code,
                'title' => $trans?->title ?? '',
                'description' => $trans?->description ?? '',
            ];
        }
        $languages = $this->languages;
        return view('dashboard.services.edit', compact('service', 'serviceTranslations', 'languages'));
    }

    public function update(Request $request, $id)
    {
        $service = Service::findOrFail($id);
        $request->validate([
            'order' => 'required|integer',
            'icon' => 'nullable',
            'url' => 'nullable',
            'serviceTranslations.*.title' => 'required|string',
            'serviceTranslations.*.description' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            // Update basic fields only
            $service->update($request->only(['order', 'icon', 'url']));

            // إعادة إدخال الترجمات باستخدام serviceTranslations (كما في الـ form)
            $translations = $request->input('serviceTranslations', []);
            foreach ($translations as $translation) {
                ServiceTranslation::updateOrCreate(
                    ['service_id' => $service->id, 'locale' => $translation['locale']],
                    [
                        'title' => $translation['title'],
                        'description' => $translation['description']
                    ]
                );
            }

            DB::commit();

            return redirect()->route('dashboard.services.index')->with('success', 'تم تعديل القالب بنجاح.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }


    public function destroy($id)
    {
        $service = Service::findOrFail($id);

        $service->delete();

        return redirect()->route('dashboard.services.index')->with('success', 'تم حذف القالب بنجاح.');
    }
}

