<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGridSubstationRequest extends FormRequest
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
        $substation = $this->route('gridSubstation');

        return [
            'district_id' => ['sometimes', 'integer', 'exists:districts,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['sometimes', 'string', 'max:255', Rule::unique('grid_substations', 'code')->ignore($substation)],
            'location' => ['nullable', 'string', 'max:255'],
        ];
    }
}
