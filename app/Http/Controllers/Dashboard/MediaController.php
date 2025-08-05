<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Media;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class MediaController extends Controller
{
    public function index()
    {
        $media = Media::latest()->get();
        return response()->json($media);
    }

    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image',
        ]);

        $file = $request->file('image');
        $path = $file->store('uploads/media', 'public');

        $media = Media::create([
            'name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'uploader_id' => Auth::user()->id,
        ]);

        return response()->json($media);
    }

    public function show($id)
    {
        $media = Media::findOrFail($id);
        return response()->json($media);
    }

    public function edit($id)
    {
        $media = Media::findOrFail($id);
        return response()->json($media);
    }

    public function update(Request $request, $id)
    {
        $media = Media::findOrFail($id);

        $request->validate([
            'name' => 'nullable|string|max:255',
            'alt' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'caption' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $media->update($request->all());

        return response()->json(['message' => 'تم التحديث بنجاح']);
    }


    public function destroy($id)
    {
        $media = Media::findOrFail($id);
        Storage::disk('public')->delete($media->file_path);
        $media->delete();

        return response()->json(['message' => 'تم الحذف']);
    }
}
