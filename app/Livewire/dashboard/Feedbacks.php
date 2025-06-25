<?php

namespace App\Livewire\dashboard;

use App\Models\Language;
use App\Models\Feedback;
use App\Models\FeedbackTranslation;
use Illuminate\Http\UploadedFile;
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

            // تحقق إن كانت الأيقونة جديدة
            if ($this->feedback['image'] instanceof UploadedFile) {
                if ($feedback->image && Storage::disk('public')->exists($feedback->image)) {
                    Storage::disk('public')->delete($feedback->image);
                }
                $feedbackData['image'] = $this->feedback['image']->store('feedbacks', 'public');
            } else {
                $feedbackData['image'] = $feedback->image;
            }

            $feedback->update($feedbackData);
            $this->showAlert('Feedback updated successfully.', 'success');
        } else {
            if ($this->feedback['image'] instanceof UploadedFile) {
                $feedbackData['image'] = $this->feedback['image']->store('feedbacks', 'public');
            }

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

            if ($feedback->icon && Storage::disk('public')->exists($feedback->icon)) {
                Storage::disk('public')->delete($feedback->icon);
            }

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
