<?php

namespace App\Livewire\Admin;

use App\Models\Language;
use App\Models\Testimonial;
use App\Models\TestimonialTranslation;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Testimonials extends Component
{
    use WithPagination, WithFileUploads;

    protected $listeners = ['deleteTestimonialConfirmed'];

    public $alert = false;
    public $alertType = 'success';
    public $alertMessage = '';

    public $mode = 'index';
    public $search = '';
    public $perPage = 10;
    public $testimonialId = null;

    public $testimonial = [
        'image' => '',
        'star' => '',
        'order' => '',
        'is_approved' => true,
    ];

    public $testimonialTranslations = [];
    public $languages = [];

    public function mount()
    {
        $this->languages = Language::all();
        $this->resetTranslationForm();
    }

    protected function resetTranslationForm(): void
    {
        $this->testimonialTranslations = [];

        foreach ($this->languages as $lang) {
            $this->testimonialTranslations[] = [
                'locale' => $lang->code,
                'feedback' => '',
                'name' => '',
                'major' => '',
            ];
        }
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
        $this->testimonialId = $id;

        $testimonial = Testimonial::with('translations')->findOrFail($id);

        $this->testimonial = [
            'image' => $testimonial->image,
            'star' => $testimonial->star,
            'order' => $testimonial->order,
            'is_approved' => (bool) $testimonial->is_approved,
        ];

        $this->testimonialTranslations = [];
        foreach ($this->languages as $lang) {
            $trans = $testimonial->translations->firstWhere('locale', $lang->code);
            $this->testimonialTranslations[] = [
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
        $this->testimonialId = null;
        $this->testimonial = ['image' => '', 'star' => '', 'order' => '', 'is_approved' => true];
        $this->resetTranslationForm();
    }

    public function save()
    {
        $this->validate([
            'testimonial.order' => 'required|integer',
            'testimonial.image' => 'nullable',
            'testimonial.is_approved' => 'boolean',
            'testimonialTranslations.*.feedback' => 'required|string',
            'testimonialTranslations.*.name' => 'required|string',
            'testimonialTranslations.*.major' => 'required|string',
        ]);

        $data = $this->testimonial;
        $data['is_approved'] = (bool) ($data['is_approved'] ?? false);

        if ($this->testimonialId) {
            $testimonial = Testimonial::findOrFail($this->testimonialId);
            $testimonial->update($data);
            $this->showAlert('Êã ÊÍÏíË ÇáÔåÇÏÉ ÈäÌÇÍ.', 'success');
        } else {
            $testimonial = Testimonial::create($data);
            $this->showAlert('Êã ÅÖÇÝÉ ÇáÔåÇÏÉ ÈäÌÇÍ.', 'success');
        }

        foreach ($this->testimonialTranslations as $translation) {
            TestimonialTranslation::updateOrCreate(
                ['feedback_id' => $testimonial->id, 'locale' => $translation['locale']],
                ['feedback' => $translation['feedback'], 'name' => $translation['name'], 'major' => $translation['major']]
            );
        }

        $this->resetForm();
        $this->resetPage();
        $this->mode = 'index';
    }

    public function confirmDelete($id)
    {
        $this->dispatchBrowserEvent('show-delete-confirmation', ['id' => $id]);
    }

    public function deleteTestimonialConfirmed($id)
    {
        try {
            $testimonial = Testimonial::findOrFail($id);
            $testimonial->delete();

            $this->dispatch('testimonial-deleted-success');
            $this->showAlert('Êã ÍÐÝ ÇáÔåÇÏÉ ÈäÌÇÍ.', 'success');
        } catch (\Exception $e) {
            logger()->error('ÎØÃ ÃËäÇÁ ÍÐÝ ÇáÔåÇÏÉ: ' . $e->getMessage());
            $this->dispatch('testimonial-delete-failed');
            $this->showAlert('ÊÚÐÑ ÍÐÝ ÇáÔåÇÏÉ¡ ÍÇæá ãÑÉ ÃÎÑì.', 'danger');
        }

        $this->resetPage();
    }

    public function render()
    {
        $testimonials = Testimonial::with('translations')->paginate($this->perPage);
        return view('livewire.dashboard.testimonials', compact('testimonials'));
    }
}

