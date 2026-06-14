<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CheckTokenRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'token' => [
                'nullable',
                'string',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (empty($value) && ! $this->bearerToken()) {
                        $fail('The token field is required when no Authorization header is present.');
                    }
                },
            ],
        ];

        if (! $this->has('token') && ! $this->bearerToken()) {
            $rules['token'] = ['required', 'string'];
        }

        return $rules;
    }
}
