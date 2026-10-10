<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSolarInstallationRequest extends FormRequest
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
        $installation = $this->route('installation');

        return [
            'grid_substation_id' => ['sometimes', 'integer', 'exists:grid_substations,id'],
            'meter_id' => ['sometimes', 'string', 'max:255', Rule::unique('solar_installations', 'meter_id')->ignore($installation)],
            'capacity_kw' => ['sometimes', 'numeric', 'gt:0', 'max:99999999.99'],
            'address' => ['nullable', 'string', 'max:255'],
            'installed_at' => ['nullable', 'date'],
            'status' => ['sometimes', 'string', 'in:active,maintenance,suspended'],
        ];
    }
}
