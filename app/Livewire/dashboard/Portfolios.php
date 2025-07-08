<?php

namespace App\Livewire\dashboard;

use App\Models\Language;
use App\Models\Portfolio;
use App\Models\PortfolioTranslation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Livewire\WithPagination;

class Portfolios extends Component
{
    use WithPagination, WithFileUploads;
    protected $listeners = ['deletePortfolioConfirmed'];


    public $alert = false;
    public $alertType = 'success';
    public $alertMessage = '';

    public $mode = 'index';
    public $search = '';
    public $perPage = 10;
    public $portfolioId = null;

    public $portfolio = [
        'default_image' => '',
        'images' => [],
        'imagesMultiple' => [],
        'delivery_date' => '',
        'order' => '',
    ];

    public $portfolioTranslations = [];
    public $languages = [];
    public $typeSuggestions = [];


    public function mount()
    {
        $languages = Language::get();
        $this->languages = $languages;

        $this->typeSuggestions = $languages->mapWithKeys(function ($lang) {
            $rawTypes = PortfolioTranslation::where('locale', $lang->code)
                            ->whereNotNull('type')
                            ->pluck('type')
                            ->toArray();

            $allTypes = collect($rawTypes)
                ->flatMap(function ($typeString) {
                    // نفصل بالـ , أو الفاصلة العربية
                    return collect(preg_split('/[,،]/u', $typeString))
                            ->map(fn($v) => trim($v))
                            ->filter();
                })
                ->unique()
                ->values();

            return [$lang->code => $allTypes];
        })->toArray();
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
        $this->portfolioId = $id;

        $portfolio = Portfolio::findOrFail($id);

        $this->portfolio = [
            'default_image' => $portfolio->default_image,
            'images' => [],
            'imagesMultiple' => json_decode($portfolio->images),
            'delivery_date' => $portfolio->delivery_date,
            'order' => $portfolio->order,
        ];

        $this->portfolioTranslations = [];
        foreach ($this->languages as $lang) {
            $trans = $portfolio->translations->firstWhere('locale', $lang->code);
            $this->portfolioTranslations[] = [
                'locale' => $lang->code,
                'title' => $trans?->title ?? '',
                'type' => $trans?->type ?? '',
                'materials' => $trans?->materials ?? '',
                'link' => $trans?->link ?? '',
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
        $this->portfolioId = null;
        $this->portfolio = [
            'default_image' => '',
            'images' => [],
            'imagesMultiple' => [],
            'delivery_date' => '',
            'order' => '',
        ];

        $this->portfolioTranslations = [];
        foreach ($this->languages as $lang) {
            $this->portfolioTranslations[] = [
                'locale' => $lang->code,
                'title' => '',
                'type' => '',
                'materials' => '',
                'link' => '',
            ];
        }
    }

    public function save()
    {
        $validated = $this->validate([
            'portfolio.order' => 'required|integer',
            'portfolio.default_image' => 'nullable', // optional file
            'portfolio.images' => 'nullable', // optional file
            'portfolio.delivery_date' => 'required|date',
            'portfolioTranslations.*.title' => 'required|string',
            'portfolioTranslations.*.type' => 'required|string',
            'portfolioTranslations.*.materials' => 'required|string',
            'portfolioTranslations.*.link' => 'nullable|string',
        ]);

        $portfolioData = $this->portfolio;

        if ($this->portfolioId) {
            $portfolio = Portfolio::findOrFail($this->portfolioId);

            // تحقق إن كانت الأيقونة جديدة
            if ($this->portfolio['default_image'] instanceof UploadedFile) {
                if ($portfolio->default_image && Storage::disk('public')->exists($portfolio->default_image)) {
                    Storage::disk('public')->delete($portfolio->default_image);
                }
                $portfolioData['default_image'] = $this->portfolio['default_image']->store('icons', 'public');
            } else {
                $portfolioData['default_image'] = $portfolio->default_image;
            }

            if($this->portfolio['imagesMultiple']){
                foreach($this->portfolio['imagesMultiple'] as $image){
                    if($image instanceof UploadedFile){
                        $portfolioData['images'][] = $image->store('icons', 'public');
                    }
                }
            }else{
                $portfolioData['images'] = $portfolioData['imagesMultiple'];
            }
            $portfolioData['images'] = json_encode($portfolioData['images']);

            $portfolio->update($portfolioData);
            $this->showAlert('Portfolio updated successfully.', 'success');
        } else {
            if ($this->portfolio['default_image'] instanceof UploadedFile) {
                $portfolioData['default_image'] = $this->portfolio['default_image']->store('icons', 'public');
            }

            if($this->portfolio['imagesMultiple']){
                foreach($this->portfolio['imagesMultiple'] as $image){
                    if($image instanceof UploadedFile){
                        $portfolioData['images'][] = $image->store('icons', 'public');
                    }
                }
                $portfolioData['images'] = $portfolioData['images'];
            }else{
                $portfolioData['images'] = $portfolioData['imagesMultiple'];
            }
            $portfolioData['images'] = json_encode($portfolioData['images']);

            $portfolio = Portfolio::create($portfolioData);
            $this->showAlert('Portfolio added successfully.', 'success');
        }

        // حفظ الترجمات
        foreach ($this->portfolioTranslations as $translation) {
            PortfolioTranslation::updateOrCreate(
                [
                    'portfolio_id' => $portfolio->id,
                    'locale' => $translation['locale']
                ],
                [
                    'title' => $translation['title'],
                    'type' => $translation['type'],
                    'materials' => $translation['materials'],
                    'link' => $translation['link']
                ]
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
    public function deletePortfolioConfirmed($id)
    {
        try {
        $portfolio = Portfolio::findOrFail($id);

        if ($portfolio->default_image && Storage::disk('public')->exists($portfolio->default_image)) {
            Storage::disk('public')->delete($portfolio->default_image);
        }

        if($portfolio->images){
            $images = json_decode($portfolio->images);
            foreach($images as $image){
                if(Storage::disk('public')->exists($image)){
                    Storage::disk('public')->delete($image);
                }
            }
        }

        $portfolio->delete();

        $this->dispatch('portfolio-deleted-success');
        $this->showAlert('✅ تم حذف الخدمة بنجاح', 'success');
        } catch (\Exception $e) {
        logger()->error('خطأ أثناء الحذف: ' . $e->getMessage());
        $this->dispatch('portfolio-delete-failed');
        $this->showAlert('❌ حدث خطأ أثناء الحذف', 'danger');
        }
        $this->resetPage();
    }

    public function render()
    {
        $portfolios = Portfolio::with('translations')->paginate($this->perPage);
        return view('livewire.portfolios', compact('portfolios'));
    }
}
