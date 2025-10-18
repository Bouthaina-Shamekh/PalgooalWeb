<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDomainDnsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nameservers'   => ['required', 'array', 'min:2', 'max:12'],
            'nameservers.*' => ['nullable', 'string', 'distinct', 'regex:/^([a-z0-9-]+\.)+[a-z]{2,}$/i', 'max:255'],
            'notes'         => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'nameservers.required' => __('Please provide at least two nameservers.'),
            'nameservers.min'      => __('Please provide at least two nameservers.'),
            'nameservers.max'      => __('You can add up to :max nameservers.'),
            'nameservers.*.regex'  => __('Each nameserver must be a valid hostname (e.g., ns1.example.com).'),
            'nameservers.*.distinct' => __('Duplicate nameservers are not allowed.'),
        ];
    }
}
