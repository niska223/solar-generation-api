<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SolarInstallationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'grid_substation_id' => $this->grid_substation_id,
            'meter_id' => $this->meter_id,
            'capacity_kw' => $this->capacity_kw,
            'address' => $this->address,
            'installed_at' => optional($this->installed_at)->toDateString(),
            'status' => $this->status,
            'grid_substation' => new GridSubstationResource($this->whenLoaded('gridSubstation')),
            'readings_count' => $this->whenCounted('generationReadings'),
            'latest_reading' => new GenerationReadingResource($this->whenLoaded('latestReading')),
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}
