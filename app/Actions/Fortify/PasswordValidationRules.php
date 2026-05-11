<?php

namespace App\Actions\Fortify;

trait PasswordValidationRules
{
    /**
     * Get the validation rules used to validate passwords.
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function passwordRules(): array
    {
        return ['required', 'string', 'min:8', 'confirmed'];
    }

    /**
     * Get localized validation messages used for password validation.
     *
     * @return array<string, string>
     */
    protected function passwordValidationMessages(string $field = 'password'): array
    {
        return [
            "$field.required" => __('Please enter a password.'),
            "$field.string" => __('Password must be a valid text value.'),
            "$field.min" => __('Password must be at least :min characters.'),
            "$field.confirmed" => __('Password confirmation does not match.'),
        ];
    }
}
