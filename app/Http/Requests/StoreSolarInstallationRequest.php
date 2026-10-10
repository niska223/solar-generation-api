<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSolarInstallationRequest extends FormRequest
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
        return [
            'grid_substation_id' => ['required', 'integer', 'exists:grid_substations,id'],
            'meter_id' => ['required', 'string', 'max:255'],
            'capacity_kw' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'address' => ['nullable', 'string', 'max:255'],
            'installed_at' => ['nullable', 'date'],
            'status' => ['sometimes', 'string', 'in:active,maintenance,suspended'],
        ];
    }
}
