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
    public function index(Request $request)
    {
        $this->authorize('viewAny', Service::class);

        $search  = trim((string) $request->get('search', ''));
        $perPage = in_array((int) $request->get('per_page'), [10, 25, 50])
            ? (int) $request->get('per_page') : 10;

        $services = Service::with('translations')
            ->when($search !== '', function ($q) use ($search) {
                $q->whereHas('translations', function ($t) use ($search) {
                    $t->where('title', 'like', '%' . addcslashes($search, '%_\\') . '%');
                });
            })
            ->orderBy('order')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('dashboard.services.index', compact('services', 'search', 'perPage'));
    }

    public function create()
    {
        $this->authorize('create', Service::class);
        $service = new Service();
        $serviceTranslations = [];
        $languages = $this->languages;
        return view('dashboard.services.create', compact('service', 'serviceTranslations', 'languages'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Service::class);
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
            $service = Service::create($request->only(['order', 'icon', 'url']));

            foreach ($request->serviceTranslations as $translation) {
                ServiceTranslation::create([
                    'service_id'  => $service->id,
                    'locale'      => $translation['locale'],
                    'title'       => $translation['title'],
                    'description' => $translation['description'],
                ]);
            }

            DB::commit();

            return redirect()->route('dashboard.services.index')
                ->with('ok', t('dashboard.Service_Created', 'Service created successfully.'));
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', t('dashboard.Service_Error', 'An error occurred. Please try again.'));
        }
    }

    public function edit($id)
    {
        $this->authorize('viewAny', Service::class);
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
        $this->authorize('update', $service);
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

            return redirect()->route('dashboard.services.index')
                ->with('ok', t('dashboard.Service_Updated', 'Service updated successfully.'));
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', t('dashboard.Service_Error', 'An error occurred. Please try again.'));
        }
    }


    public function destroy($id)
    {
        $service = Service::findOrFail($id);
        $this->authorize('delete', $service);

        $service->delete();

        return redirect()->route('dashboard.services.index')
            ->with('ok', t('dashboard.Service_Deleted', 'Service deleted successfully.'));
    }
}

