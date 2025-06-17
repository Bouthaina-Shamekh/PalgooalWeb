<?php

namespace App\Livewire;

use App\Models\Media;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class MediaLibrary extends Component
{
    use WithFileUploads;

    public $files = [];
    public $selectedMedia;
    public $alt;
    public $title;
    public $caption;
    public $description;
    public $showModal = false;
    public $search = '';
    public $fileTypeFilter = '';

    public function updatedFiles()
    {
        foreach ($this->files as $file) {
            $path = $file->store('media/' . now()->format('Y/m'), 'public');

            Media::create([
                'name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'uploader_id' => auth()->id(),
            ]);
        }

        $this->files = [];
    }

    public function selectMedia($mediaId)
    {
        $this->selectedMedia = Media::find($mediaId);

        if ($this->selectedMedia) {
            $this->alt = $this->selectedMedia->alt;
            $this->title = $this->selectedMedia->title;
            $this->caption = $this->selectedMedia->caption;
            $this->description = $this->selectedMedia->description;
            $this->showModal = true;
        }
    }

    public function closeModal()
    {
        $this->reset(['showModal', 'selectedMedia', 'alt', 'title', 'caption', 'description']);
    }

    public function saveMediaDetails()
    {
        if ($this->selectedMedia) {
            $this->selectedMedia->update([
                'alt' => $this->alt,
                'title' => $this->title,
                'caption' => $this->caption,
                'description' => $this->description,
            ]);

            session()->flash('message', 'Media details updated!');
        }
    }

    public function deleteMedia()
    {
        if ($this->selectedMedia) {
            Storage::disk('public')->delete($this->selectedMedia->file_path);
            $this->selectedMedia->delete();
            $this->selectedMedia = null;
        }
    }

    public function render()
    {
        $query = Media::query();

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        if ($this->fileTypeFilter) {
            switch ($this->fileTypeFilter) {
                case 'images':
                    $query->where('mime_type', 'like', 'image/%');
                    break;
                case 'videos':
                    $query->where('mime_type', 'like', 'video/%');
                    break;
                case 'documents':
                    $query->whereIn('mime_type', [
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    ]);
                    break;
                case 'spreadsheets':
                    $query->whereIn('mime_type', [
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ]);
                    break;
                case 'archives':
                    $query->whereIn('mime_type', [
                        'application/zip',
                        'application/x-rar-compressed',
                        'application/x-7z-compressed',
                    ]);
                    break;
            }
        }

        return view('livewire.media-library', [
            'mediaItems' => $query->latest()->get(),
        ]);
    }
}
