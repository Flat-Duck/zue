<?php

namespace App\Http\Requests;

use App\Models\ScopeContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ScopeContextRequest extends FormRequest
{
    public function authorize(): bool
    {
        $context = $this->route('scope_context');

        return $context instanceof ScopeContext
            ? $this->user()?->can('update', $context) ?? false
            : $this->user()?->can('create', ScopeContext::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $context = $this->route('scope_context');
        $editing = $context instanceof ScopeContext;

        return [
            // The key is what code and scopes refer to. A built-in one is fixed
            // for good; any other may only be set when the context is created.
            'key' => [
                $editing ? 'prohibited' : 'required',
                'string',
                'max:64',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('scope_contexts', 'key'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'carves_out_managers' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'key.regex' => __('scopes.contexts_key_format'),
            'key.prohibited' => __('scopes.contexts_key_locked'),
        ];
    }
}
