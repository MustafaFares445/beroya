<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCarRequest extends FormRequest
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
            'market_id' => ['required', 'integer', 'exists:markets,id'],
            'model_id' => ['required', 'integer', 'exists:models,id'],
            'year' => ['required', 'integer', 'min:1900', 'max:2100'],
            'gasoline' => ['required', 'string', 'max:30'],
            'engine' => ['required', 'string', 'max:30'],
            'transmission' => ['required', 'string', 'max:50'],
            'color' => ['required', 'string', 'max:50'],
            'distance' => ['required', 'string', 'max:100'],
            'imported' => ['required', 'string', 'max:50'],
            'spray' => ['required', 'string'],
            'status' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:1000'],
            'plateNumber' => ['required', 'string', 'max:50'],
            'notes' => ['required', 'string', 'max:500'],
            'price' => ['required', 'integer', 'min:1'],
            'possession' => ['required', 'string'],
            'owner_name' => ['required', 'string', 'max:100'],
            'owner_phone' => ['required', 'string', 'max:15'],
            'gallery_id' => ['required', 'integer', 'exists:galleries,id'],
            'image_1' => ['nullable', 'file', 'max:51200'],
            'image_2' => ['nullable', 'file', 'max:51200'],
            'image_3' => ['nullable', 'file', 'max:51200'],
            'image_4' => ['nullable', 'file', 'max:51200'],
            'image_5' => ['nullable', 'file', 'max:51200'],
            'image_6' => ['nullable', 'file', 'max:51200'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'image_1' => $this->file('image_1') ?? $this->file('image1'),
            'image_2' => $this->file('image_2') ?? $this->file('image2'),
            'image_3' => $this->file('image_3') ?? $this->file('image3'),
            'image_4' => $this->file('image_4') ?? $this->file('image4'),
            'image_5' => $this->file('image_5') ?? $this->file('image5'),
            'image_6' => $this->file('image_6') ?? $this->file('image6'),
        ]);
    }
}
