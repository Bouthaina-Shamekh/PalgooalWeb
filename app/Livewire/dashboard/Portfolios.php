<?php

namespace App\Livewire\Dashboard;

use App\Models\Language;
use App\Models\Portfolio;
use App\Models\PortfolioTranslation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
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
        'implementation_period_days' => '',
        'slug' => '',
        'client' => '',
    ];

    public $portfolioTranslations = [];
    public $languages = [];
    public $typeSuggestions = [];
    public $statusSuggestions = [];

    public function mount()
    {
        $this->languages = Language::get();

        $this->typeSuggestions = collect($this->languages)->mapWithKeys(function ($lang) {
            $types = PortfolioTranslation::where('locale', $lang->code)
                ->whereNotNull('type')
                ->pluck('type')
                ->flatMap(fn($str) => collect(preg_split('/[,،]/u', $str))->map('trim')->filter())
                ->unique()
                ->values();
            return [$lang->code => $types];
        })->toArray();

        $this->statusSuggestions = [
            'ar' => ['مفعل', 'غير مفعل', 'مكتمل'],
            'en' => ['Active', 'Inactive', 'Completed'],
        ];
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
            'implementation_period_days' => $portfolio->implementation_period_days,
            'slug' => $portfolio->slug,
            'client' => $portfolio->client,
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
                'status' => $trans?->status ?? '',
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
            'implementation_period_days' => '',
            'slug' => '',
            'client' => '',
            'description' => '',
        ];

        $this->portfolioTranslations = [];
        foreach ($this->languages as $lang) {
            $this->portfolioTranslations[] = [
                'locale' => $lang->code,
                'title' => '',
                'type' => '',
                'materials' => '',
                'link' => '',
                'status' => '',
            ];
        }
    }

    public function save()
    {
        $this->validate([
            'portfolio.order' => 'required|integer',
            'portfolio.default_image' => 'nullable',
            'portfolio.images' => 'nullable',
            'portfolio.delivery_date' => 'required|date',
            'portfolio.implementation_period_days' => 'required|integer',
            'portfolio.slug' => 'required|string|unique:portfolios,slug,' . $this->portfolioId,
            'portfolio.client' => 'nullable|string',
            'portfolioTranslations.*.title' => 'required|string',
            'portfolioTranslations.*.type' => 'required|string',
            'portfolioTranslations.*.materials' => 'required|string',
            'portfolioTranslations.*.link' => 'nullable|string',
            'portfolioTranslations.*.status' => 'nullable|string',
            'portfolioTranslations.*.description' => 'nullable|string',
        ]);

        $portfolioData = $this->portfolio;

        // حفظ default_image
        if ($this->portfolio['default_image'] instanceof UploadedFile) {
            $portfolioData['default_image'] = $this->portfolio['default_image']->store('portfolios', 'public');
        } elseif ($this->portfolioId) {
            $existing = Portfolio::findOrFail($this->portfolioId);
            $portfolioData['default_image'] = $existing->default_image;
        }

        // رفع الصور الإضافية
        $uploadedImages = [];
        if (!empty($this->portfolio['imagesMultiple'])) {
            foreach ($this->portfolio['imagesMultiple'] as $image) {
                if ($image instanceof UploadedFile) {
                    $uploadedImages[] = $image->store('portfolios', 'public');
                } elseif (is_string($image)) {
                    $uploadedImages[] = $image;
                }
            }
        }
        $portfolioData['images'] = json_encode($uploadedImages);

        // حفظ البيانات
        $portfolio = $this->portfolioId
            ? tap(Portfolio::findOrFail($this->portfolioId))->update($portfolioData)
            : Portfolio::create($portfolioData);

        // حفظ الترجمات
        foreach ($this->portfolioTranslations as $translation) {
            PortfolioTranslation::updateOrCreate(
                ['portfolio_id' => $portfolio->id, 'locale' => $translation['locale']],
                [
                    'title' => $translation['title'],
                    'type' => $translation['type'],
                    'materials' => $translation['materials'],
                    'link' => $translation['link'],
                    'status' => $translation['status'],
                    'description' => $translation['description'],
                ]
            );
        }

        $this->showAlert($this->portfolioId ? 'Portfolio updated successfully.' : 'Portfolio added successfully.');
        $this->resetForm();
        $this->resetPage();
        $this->mode = 'index';
    }

    public function confirmDelete($id)
    {
        $this->dispatchBrowserEvent('show-delete-confirmation', ['id' => $id]);
    }

    public function deletePortfolioConfirmed($id)
    {
        try {
            $portfolio = Portfolio::findOrFail($id);

            if ($portfolio->default_image && Storage::disk('public')->exists($portfolio->default_image)) {
                Storage::disk('public')->delete($portfolio->default_image);
            }

            $images = json_decode($portfolio->images ?? '[]');
            foreach ($images as $image) {
                if (Storage::disk('public')->exists($image)) {
                    Storage::disk('public')->delete($image);
                }
            }

            $portfolio->delete();

            $this->dispatch('portfolio-deleted-success');
            $this->showAlert('✅ تم حذف العمل بنجاح', 'success');
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
