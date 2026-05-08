<?php

namespace App\Http\Requests\Admin;

use App\Models\Sections\SectionDefinition;
use Illuminate\Foundation\Http\FormRequest;

class ImportSectionDefinitionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', SectionDefinition::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'definitions_json' => ['required', 'file', 'max:1024'],
        ];
    }
}
