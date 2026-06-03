<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountReceivedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'received' => ['required', 'string', 'in:0,1'],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'week_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
