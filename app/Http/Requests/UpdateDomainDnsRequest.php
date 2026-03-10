<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDomainDnsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $nameservers = $this->input('nameservers');
        $nameserverIps = $this->input('nameserver_ips');

        if (is_array($nameservers)) {
            $pairs = collect($nameservers)
                ->map(function ($value, $index) use ($nameserverIps) {
                    return [
                        'nameserver' => is_string($value) ? trim($value) : $value,
                        'ip' => is_array($nameserverIps) ? ($nameserverIps[$index] ?? null) : null,
                    ];
                })
                ->filter(fn ($pair) => filled($pair['nameserver']));

            $filtered = $pairs
                ->pluck('nameserver')
                ->values()
                ->all();

            $filteredIps = $pairs
                ->map(fn ($pair) => is_string($pair['ip']) ? trim($pair['ip']) : $pair['ip'])
                ->values()
                ->all();

            $this->merge([
                'nameservers' => $filtered,
                'nameserver_ips' => $filteredIps,
            ]);
        } elseif (is_array($nameserverIps)) {
            $this->merge([
                'nameserver_ips' => collect($nameserverIps)
                    ->map(fn ($value) => is_string($value) ? trim($value) : $value)
                    ->values()
                    ->all(),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'nameservers'   => ['required', 'array', 'min:2', 'max:12'],
            'nameservers.*' => ['nullable', 'string', 'distinct', 'regex:/^([a-z0-9-]+\.)+[a-z]{2,}$/i', 'max:255'],
            'nameserver_ips' => ['nullable', 'array'],
            'nameserver_ips.*' => ['nullable', 'ipv4'],
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
            'nameserver_ips.*.ipv4' => __('Each glue record IP must be a valid IPv4 address.'),
        ];
    }
}
