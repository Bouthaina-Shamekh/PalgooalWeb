<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubscriptionThemeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Gates are handled at the route/controller level
    }

    public function rules(): array
    {
        $hexRule = ['nullable', 'string', 'regex:/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'];
        $sizeRule = ['nullable', 'string', 'regex:/^\d+(\.\d+)?(px|rem|em|%)$|^0$/'];
        $fontRule = ['nullable', 'string', 'max:120', 'regex:/^[a-zA-Z0-9 ,\-\'\"]+$/'];
        $weightRule = ['nullable', 'string', Rule::in([
            '100','200','300','400','500','600','700','800','900',
            'thin','extralight','light','normal','medium','semibold','bold','extrabold','black',
        ])];

        return [
            // Colors
            'color_primary'   => $hexRule,
            'color_secondary' => $hexRule,
            'color_surface'   => $hexRule,
            'color_muted'     => $hexRule,
            'color_heading'   => $hexRule,
            'color_body'      => $hexRule,
            'color_border'    => $hexRule,

            // Typography
            'font_primary'    => $fontRule,
            'font_heading'    => $fontRule,
            'base_font_size'  => $sizeRule,
            'weight_normal'   => $weightRule,
            'weight_bold'     => $weightRule,

            // Shape
            'radius_sm'       => $sizeRule,
            'radius_md'       => $sizeRule,
            'radius_lg'       => $sizeRule,
            'radius_xl'       => $sizeRule,

            // Buttons
            'button_radius'   => $sizeRule,
            'button_style'    => ['nullable', 'string', Rule::in(['filled', 'outline', 'ghost'])],
        ];
    }

    public function messages(): array
    {
        return [
            '*.regex' => 'The :attribute field has an invalid format.',
        ];
    }

    /**
     * Return only the known theme keys as a clean array (nulls stripped).
     */
    public function themeData(): array
    {
        return array_filter($this->only([
            'color_primary', 'color_secondary', 'color_surface',
            'color_muted', 'color_heading', 'color_body', 'color_border',
            'font_primary', 'font_heading', 'base_font_size',
            'weight_normal', 'weight_bold',
            'radius_sm', 'radius_md', 'radius_lg', 'radius_xl',
            'button_radius', 'button_style',
        ]), fn ($v) => $v !== null);
    }
}
