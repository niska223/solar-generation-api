<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProvinceRequest extends FormRequest
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
        $province = $this->route('province');

        return [
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('provinces', 'name')->ignore($province)],
            'code' => ['sometimes', 'string', 'max:10', Rule::unique('provinces', 'code')->ignore($province)],
        ];
    }
}
