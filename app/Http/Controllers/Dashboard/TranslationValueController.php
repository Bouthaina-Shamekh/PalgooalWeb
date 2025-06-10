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

        // Clear cache to update translation immediately
        cache()->forget("translation.{$locale}.{$key}");
    }

    return redirect()->route('dashboard.translation-values.index')->with('success', 'تم التحديث بنجاح');
}


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($key)
    {
        // Get all translations for this key
        $translations = TranslationValue::where('key', $key)->get();
        
        foreach ($translations as $translation) {
            // Delete cache for this key and language
            cache()->forget("translation.{$translation->locale}.{$translation->key}");
            // Delete the translation itself
            $translation->delete();
        }

        return redirect()->route('dashboard.translation-values.index')->with('success', 'تم الحذف بنجاح');
    }



    public function export()
    {
        $translations = TranslationValue::all();
        $csvHeader = ['key', 'locale', 'value'];
        $rows = [];
        foreach ($translations as $translation) {
            $rows[] = [
                $translation->key,
                $translation->locale,
                $translation->value,
            ];
        }
        
        $filename = 'translations_export_' . now()->format('Y_m_d_His') . '.csv';
        $handle = fopen('php://output', 'w');
        header('Content-Type: text/csv');
        header("Content-Disposition: attachment; filename={$filename}");
        
        fputcsv($handle, $csvHeader);
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);
        exit;
    }

    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);
        
        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== false) {
            TranslationValue::updateOrCreate(
                ['key' => $row[0], 'locale' => $row[1]],
                ['value' => $row[2]]
            );

            // Invalidate cache
            cache()->forget("translation.{$row[1]}.{$row[0]}");
        }

        fclose($handle);
        return redirect()->back()->with('success', 'تم استيراد الترجمات بنجاح');
    }
}
