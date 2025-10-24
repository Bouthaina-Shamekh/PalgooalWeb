<?php

namespace App\Livewire\Dashboard\Sections;

use App\Models\SectionTranslation;
use Illuminate\Support\Arr;

class CtaSection extends BaseSectionComponent
{
    public function mount(): void
    {
        parent::mount();

        foreach ($this->languages as $lang) {
            $translation = $this->section->translations->firstWhere('locale', $lang->code);
            $content = is_array($translation?->content) ? $translation->content : [];

            $this->translationsData[$lang->code] = [
                'title'               => $translation?->title ?? '',
                'subtitle'            => Arr::get($content, 'subtitle', ''),
                'badge'               => Arr::get($content, 'badge', ''),
                'primary_button_text' => Arr::get($content, 'primary_button_text', Arr::get($content, 'button_text', '')),
                'primary_button_url'  => Arr::get($content, 'primary_button_url', Arr::get($content, 'button_url', '')),
            ];
        }
    }

    public function updateCtaSection(): void
    {
        foreach ($this->translationsData as $locale => $data) {
            $translation = SectionTranslation::firstOrNew([
                'section_id' => $this->section->id,
                'locale'     => $locale,
            ]);

            $translation->title = Arr::get($data, 'title', '');
            $translation->content = [
                'subtitle'            => Arr::get($data, 'subtitle', ''),
                'badge'               => Arr::get($data, 'badge', ''),
                'primary_button_text' => Arr::get($data, 'primary_button_text', ''),
                'primary_button_url'  => Arr::get($data, 'primary_button_url', ''),
            ];

            $translation->save();
        }

        $this->section->order = $this->order;
        $this->section->save();

        $translatorValue = __('section.cta_updated');
        $fallbackMessage = 'CTA section updated successfully.';

        session()->flash('success', $translatorValue === 'section.cta_updated' ? $fallbackMessage : $translatorValue);
    }

    public function render()
    {
        return view('livewire.dashboard.sections.cta-section');
    }
}
