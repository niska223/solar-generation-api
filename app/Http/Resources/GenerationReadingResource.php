<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GenerationReadingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'solar_installation_id' => $this->solar_installation_id,
            'recorded_at' => optional($this->recorded_at)->toISOString(),
            'power_kw' => $this->power_kw,
            'energy_kwh' => $this->energy_kwh,
            'voltage' => $this->voltage,
            'solar_installation' => new SolarInstallationResource($this->whenLoaded('solarInstallation')),
            'created_at' => optional($this->created_at)->toISOString(),
        ];
    }
}
