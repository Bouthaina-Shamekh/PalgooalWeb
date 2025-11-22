<?php

namespace App\Livewire\Admin\Sections;

use App\Models\SectionTranslation;
use Illuminate\Support\Arr;

class FaqSection extends BaseSectionComponent
{
    public function mount(): void
    {
        parent::mount();

        foreach ($this->languages as $lang) {
            $translation = $this->section->translations->firstWhere('locale', $lang->code);
            $content = is_array($translation?->content) ? $translation->content : [];

            $items = Arr::get($content, 'items', Arr::get($content, 'faq', []));
            if (!is_array($items)) {
                $items = [];
            }

            $normalizedItems = array_map(function ($item) {
                $question = '';
                $answer = '';

                if (is_array($item)) {
                    $question = trim((string)($item['question'] ?? ''));
                    $answer = trim((string)($item['answer'] ?? ''));
                } elseif (is_string($item)) {
                    $question = trim($item);
                }

                return [
                    'question' => $question,
                    'answer'   => $answer,
                ];
            }, $items);

            $this->translationsData[$lang->code] = [
                'title'    => $translation?->title ?? '',
                'subtitle' => Arr::get($content, 'subtitle', ''),
                'items'    => $normalizedItems,
            ];
        }
    }

    public function updateFaqSection(): void
    {
        foreach ($this->translationsData as $locale => $data) {
            $translation = SectionTranslation::firstOrNew([
                'section_id' => $this->section->id,
                'locale'     => $locale,
            ]);

            $items = collect($data['items'] ?? [])
                ->map(function ($item) {
                    $question = trim((string)Arr::get($item, 'question', ''));
                    $answer = trim((string)Arr::get($item, 'answer', ''));

                    if ($question === '' && $answer === '') {
                        return null;
                    }

                    return [
                        'question' => $question,
                        'answer'   => $answer,
                    ];
                })
                ->filter()
                ->values()
                ->all();

            $translation->title = Arr::get($data, 'title', '');
            $translation->content = [
                'subtitle' => Arr::get($data, 'subtitle', ''),
                'items'    => $items,
            ];

            $translation->save();
        }

        $this->section->order = $this->order;
        $this->section->save();

        $message = __('section.faq_updated');
        if ($message === 'section.faq_updated') {
            $message = 'تم تحديث قسم الأسئلة الشائعة بنجاح.';
        }

        session()->flash('success', $message);
    }

    public function addFaq(string $locale): void
    {
        if (!isset($this->translationsData[$locale]['items']) || !is_array($this->translationsData[$locale]['items'])) {
            $this->translationsData[$locale]['items'] = [];
        }

        $this->translationsData[$locale]['items'][] = [
            'question' => '',
            'answer'   => '',
        ];
    }

    public function removeFaq(string $locale, int $index): void
    {
        $items = $this->translationsData[$locale]['items'] ?? null;
        if (!is_array($items) || !array_key_exists($index, $items)) {
            return;
        }

        unset($this->translationsData[$locale]['items'][$index]);
        $this->translationsData[$locale]['items'] = array_values($this->translationsData[$locale]['items']);
    }

    public function render()
    {
        return view('livewire.admin.sections.faq-section');
    }
}

