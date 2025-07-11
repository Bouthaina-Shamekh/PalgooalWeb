<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $langs  = Language::paginate(10);
        return view('dashboard.lang.index', compact('langs'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('dashboard.lang.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
         $request->validate([
        'name'   => 'required|string|max:255',
        'native' => 'required|string|max:255',
        'code'   => 'required|string|max:10|unique:languages,code',
        'flag'   => 'nullable|string|max:255',
    ]);
    
        Language::create([
            'name'      => $request->name,
            'native'    => $request->native,
            'code'      => strtolower($request->code),
            'flag'      => $request->flag,
            'is_rtl'    => $request->has('is_rtl'),
            'is_active' => $request->has('is_active'),
    ]);
    return redirect()->route('dashboard.languages.index')->with('success', 'Language added successfully!');
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
    public function edit(Request $request, string $id)
    {
        $language = $id;
        return view('dashboard.lang.edit')->with('language', Language::find($id));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Language $language)
    {
        $request->validate([
            'name'   => 'required|string|max:255',
            'native' => 'required|string|max:255',
            'code'   => 'required|string|max:10|unique:languages,code,' . $language->id,
            'flag'   => 'nullable|string|max:255',
        ]);

        $language->update([
            'name'      => $request->name,
            'native'    => $request->native,
            'code'      => strtolower($request->code),
            'flag'      => $request->flag,
            'is_rtl'    => $request->has('is_rtl'),
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('dashboard.languages.index')->with('success', 'Language updated successfully!');
    }

    //  Toggle RTL
    public function toggleRtl(Language $language, Request $request)
    {
        $language->is_rtl = $request->boolean('is_rtl');
        $language->save();

        return response()->json(['success' => true]);
    }

    //  Toggle Status
    public function toggleStatus(Language $language, Request $request)
    {
        $language->is_active = $request->boolean('is_active');
        $language->save();

        return response()->json(['success' => true]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Language $language)
    {
        try {
        $language->delete();

        return response()->json(['success' => true]);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()]);
    }
    }
}
