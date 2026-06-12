<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class StorePropertyImagesRequest extends FormRequest
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
            'images' => ['required', 'array', 'min:1'],
            'images.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:51200'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $images = [];

        $singleImage = $this->file('image');
        if ($singleImage instanceof UploadedFile) {
            $images[] = $singleImage;
        }

        $multipleImages = $this->file('images');
        if ($multipleImages instanceof UploadedFile) {
            $images[] = $multipleImages;
        }

        if (is_array($multipleImages)) {
            foreach ($multipleImages as $multipleImage) {
                if ($multipleImage instanceof UploadedFile) {
                    $images[] = $multipleImage;
                }
            }
        }

        $this->merge([
            'images' => $images,
        ]);
    }
}
