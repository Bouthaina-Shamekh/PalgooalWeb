<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\TranslationValue;
use Illuminate\Http\Request;

class TranslationValueController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $localeFilter = $request->get('locale');
        $search = $request->get('search');
        $typeFilter = $request->get('type');
        $translations = TranslationValue::when($localeFilter, function ($query) use ($localeFilter) {
            $query->where('locale', $localeFilter);
        })
        ->when($search, function ($query) use ($search) {
            $query->where('key', 'like', "%$search%");
        })
        ->when($typeFilter, function ($query) use ($typeFilter) {
            if ($typeFilter === 'dashboard') {
                $query->where('key', 'like', 'dashboard.%');
            } elseif ($typeFilter === 'frontend') {
                $query->where('key', 'like', 'frontend.%');
            } elseif ($typeFilter === 'general') {
                $query->where(function ($q) {
                    $q->where('key', 'not like', 'dashboard.%')
                      ->where('key', 'not like', 'frontend.%');
                });
            }
        })
        ->get()
        ->groupBy('key');
        $languages = available_locales();
        return view('dashboard.lang.translation-values.index', compact(
            'translations',
            'languages',
            'localeFilter',
            'search',
            'typeFilter'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $languages = Language::where('is_active', true)->get();
        return view('dashboard.lang.translation-values.create', compact('languages'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'key'    => 'required|string|max:150',
            'values' => 'required|array',
        ]);

        foreach ($request->values as $locale => $value) {
            TranslationValue::updateOrCreate(
                [
                    'key'    => $request->key,
                    'locale' => $locale,
                ],
                [
                    'value' => $value,
                ]
            );
        }

        return redirect()->route('dashboard.translation-values.index')->with('success', 'تمت الإضافة بنجاح');
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($key)
    {
        $languages = Language::where('is_active', true)->get();
        $translations = TranslationValue::where('key', $key)
            ->get()
            ->keyBy('locale');

        return view('dashboard.lang.translation-values.edit', compact('key', 'languages', 'translations'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $key)
    {
        $request->validate([
            'values' => 'required|array',
        ]);

        foreach ($request->values as $locale => $value) {
            TranslationValue::updateOrCreate(
                [
                    'key'    => $key,
                    'locale' => $locale,
                ],
                [
                    'value' => $value,
                ]
            );
        }

        return redirect()->route('dashboard.translation-values.index')->with('success', 'تم التحديث بنجاح');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($key)
    {
        TranslationValue::where('key', $key)->delete();
        return redirect()->route('dashboard.translation-values.index')->with('success', 'تم الحذف بنجاح');
    }
}
