<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDistrictRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $district = $this->route('district');

        return [
            'province_id' => ['sometimes', 'integer', 'exists:provinces,id'],
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('districts', 'name')->ignore($district)],
        ];
    }
}
