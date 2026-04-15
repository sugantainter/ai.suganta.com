<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProviderCredentialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $providers = array_keys((array) config('ai.providers', []));

        return [
            'provider' => ['required', 'string', Rule::in($providers)],
            'api_key' => ['required', 'string', 'min:8', 'max:512'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
