<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
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
        return [
            'user_name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'gallery_id' => ['required', 'integer'],
            'permetions_level' => ['required', 'integer'],
            'salary' => ['required', 'integer'],
            'phone' => ['required', 'string', 'max:20'],
        ];
    }
}
