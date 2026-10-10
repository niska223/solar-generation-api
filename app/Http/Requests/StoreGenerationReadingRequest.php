<?php

namespace App\Http\Requests;

use App\Models\SolarInstallation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Validator;

class StoreGenerationReadingRequest extends FormRequest
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
            'recorded_at' => ['required', 'date'],
            'power_kw' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'energy_kwh' => ['required', 'numeric', 'min:0'],
            'voltage' => ['required', 'numeric', 'min:0', 'max:1000'],
            'solar_installation_id' => ['prohibited'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $installation = $this->route('installation');

            if (! $installation instanceof SolarInstallation) {
                return;
            }

            $recordedAt = Carbon::parse((string) $this->input('recorded_at'))->utc();

            if ($recordedAt->greaterThan(Carbon::now('UTC')->addMinutes(5))) {
                $validator->errors()->add('recorded_at', 'The recorded_at field must not be in the future.');
            }

            $power = (float) $this->input('power_kw');

            if ($power > (float) $installation->capacity_kw) {
                $validator->errors()->add(
                    'power_kw',
                    'The power_kw field must not exceed the installation capacity of '.$installation->capacity_kw.' kW.'
                );
            }

            $latestEnergy = DB::table('generation_readings')
                ->where('solar_installation_id', $installation->id)
                ->max('energy_kwh');

            if ($latestEnergy !== null && (float) $this->input('energy_kwh') < (float) $latestEnergy) {
                $validator->errors()->add(
                    'energy_kwh',
                    'The energy_kwh field must be greater than or equal to the installation\'s last recorded cumulative energy of '.$latestEnergy.' kWh.'
                );
            }
        });
    }
}
