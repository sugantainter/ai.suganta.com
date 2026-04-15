<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $providers = array_keys((array) config('ai.providers', []));

        return [
            'provider' => ['nullable', 'string', Rule::in($providers)],
            'model' => ['nullable', 'string', 'max:120'],
            'stream' => ['nullable', 'boolean'],
            'conversation_id' => ['nullable', 'integer', 'min:1'],
            'subject' => ['nullable', 'string', 'max:255'],
            'purpose' => ['nullable', 'string', 'max:100'],
            'save_history' => ['nullable', 'boolean'],
            'temperature' => ['nullable', 'numeric', 'between:0,2'],
            'max_tokens' => ['nullable', 'integer', 'between:1,8192'],
            'messages' => ['required', 'array', 'min:1'],
            'messages.*.role' => ['required', 'string', 'in:system,user,assistant'],
            'messages.*.content' => ['required', 'string'],
            'fallback_providers' => ['nullable', 'array'],
            'fallback_providers.*' => ['string', Rule::in($providers)],
        ];
    }
}
