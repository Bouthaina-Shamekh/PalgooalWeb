<?php

namespace App\Livewire\dashboard;

use App\Models\Language;
use App\Models\Feedback;
use App\Models\FeedbackTranslation;
use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;

class Feedbacks extends Component
{

    use WithPagination, WithFileUploads;
    protected $listeners = ['deleteServiceConfirmed'];

    public $alert = false;
    public $alertType = 'success';
    public $alertMessage = '';

    public $mode = 'index';
    public $search = '';
    public $perPage = 10;
    public $feedbackId = null;

    public $feedback = [
        'image' => '',
        'star' => '',
        'order' => '',
    ];

    public $feedbackTranslations = [];
    public $feedbackTranslation = [
        'feedback' => '',
        'name' => '',
        'major' => '',
        'locale' => '',
    ];
    public $languages = [];

    // for media upload
    public $mediaUpload;
    public $showMediaSection = false;

    public function updatedMediaUpload()
    {
        $this->validate([
            'mediaUpload' => 'required|image|max:2048',
        ]);

        $path = $this->mediaUpload->store('media/' . now()->format('Y/m'), 'public');

        $media = Media::create([
            'name' => $this->mediaUpload->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $this->mediaUpload->getMimeType(),
            'size' => $this->mediaUpload->getSize(),
            'uploader_id' => Auth::user()->id,

            // تعبئة الحقول الأخرى بقيم فارغة أو افتراضية
            'alt' => '',
            'title' => '',
            'caption' => '',
            'description' => '',
        ]);

        $this->feedback['image'] = $media->file_path;
        $this->mediaUpload = null;
        $this->showMediaSection = false;
    }
    public function selectImage($path)
    {
        $this->feedback['image'] = $path;
        $this->showMediaSection = false;
        session()->flash('message', 'تم اختيار الصورة بنجاح');
    }


    public function mount()
    {
        $this->languages = Language::get();
    }

    public function showAlert($message, $type = 'success')
    {
        $this->alert = true;
        $this->alertType = $type;
        $this->alertMessage = $message;
    }

    public function closeModal()
    {
        $this->alert = false;
    }

    public function showAdd()
    {
        $this->mode = 'add';
        $this->resetForm();
        $this->closeModal();
    }

    public function showEdit($id)
    {
        $this->mode = 'edit';
        $this->feedbackId = $id;

        $feedback = Feedback::findOrFail($id);

        $this->feedback = [
            'image' => $feedback->image,
            'star' => $feedback->star,
            'order' => $feedback->order,
        ];

        $this->feedbackTranslations = [];
        foreach ($this->languages as $lang) {
            $trans = $feedback->translations->firstWhere('locale', $lang->code);
            $this->feedbackTranslations[] = [
                'locale' => $lang->code,
                'feedback' => $trans?->feedback ?? '',
                'name' => $trans?->name ?? '',
                'major' => $trans?->major ?? '',
            ];
        }

        $this->closeModal();
    }

    public function showIndex()
    {
        $this->mode = 'index';
        $this->closeModal();
    }

    public function resetForm()
    {
        $this->feedbackId = null;
        $this->feedback = ['image' => '', 'star' => '', 'order' => ''];

        $this->feedbackTranslations = [];
        foreach ($this->languages as $lang) {
            $this->feedbackTranslations[] = [
                'locale' => $lang->code,
                'feedback' => '',
                'name' => '',
                'major' => '',
            ];
        }
    }

    public function save()
    {
        $validated = $this->validate([
            'feedback.order' => 'required|integer',
            'feedback.image' => 'nullable', // optional file
            'feedbackTranslations.*.feedback' => 'required|string',
            'feedbackTranslations.*.name' => 'required|string',
            'feedbackTranslations.*.major' => 'required|string',
        ]);

        $feedbackData = $this->feedback;

        if ($this->feedbackId) {
            $feedback = Feedback::findOrFail($this->feedbackId);

            $feedback->update($feedbackData);
            $this->showAlert('Feedback updated successfully.', 'success');
        } else {
            $feedback = Feedback::create($feedbackData);
            $this->showAlert('Feedback added successfully.', 'success');
        }

        // حفظ الترجمات
        foreach ($this->feedbackTranslations as $translation) {
            FeedbackTranslation::updateOrCreate(
                ['feedback_id' => $feedback->id, 'locale' => $translation['locale']],
                ['feedback' => $translation['feedback'], 'name' => $translation['name'], 'major' => $translation['major']]
            );
        }

        $this->resetForm();
        $this->resetPage();
        $this->mode = 'index';
    }
    public function confirmDelete($id)
    {
        // نرسل حدث إلى المتصفح لفتح SweetAlert
        $this->dispatchBrowserEvent('show-delete-confirmation', ['id' => $id]);
    }
    public function deleteFeedbackConfirmed($id)
    {
        try {
            $feedback = Feedback::findOrFail($id);

            $feedback->delete();

            $this->dispatch('feedback-deleted-success');
            $this->showAlert('✅ تم حذف التقييم بنجاح', 'success');
        } catch (\Exception $e) {
            logger()->error('خطأ أثناء الحذف: ' . $e->getMessage());
            $this->dispatch('feedback-delete-failed');
            $this->showAlert('❌ حدث خطأ أثناء الحذف', 'danger');
        }
        $this->resetPage();
    }

    public function render()
    {
        $feedbacks = Feedback::with('translations')->paginate($this->perPage);
        return view('livewire.feedbacks', compact('feedbacks'));
    }
}
