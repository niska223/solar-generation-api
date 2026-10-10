<?php

namespace App\Support;

use App\Models\District;
use App\Models\GenerationReading;
use App\Models\GridSubstation;
use App\Models\Province;
use App\Models\SolarInstallation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Enforces an SLSEA operator's geographic read jurisdiction.
 *
 * National operators may read everything. Provincial operators may only read
 * resources inside their assigned province. District operators may only read
 * resources inside their assigned district. Any resource outside the
 * jurisdiction is treated as if it does not exist (404), preventing the
 * disclosure of out-of-scope records or counts.
 */
final class Jurisdiction
{
    public function __construct(private readonly User $operator) {}

    public function isNational(): bool
    {
        return $this->operator->isNational() || $this->operator->isAdministrator();
    }

    public function applyToProvinces(Builder $query): Builder
    {
        if ($this->isNational()) {
            return $query;
        }

        if ($this->operator->isProvincial()) {
            return $query->where('provinces.id', $this->operator->province_id);
        }

        if ($this->operator->isDistrict()) {
            return $query->whereHas('districts', fn (Builder $q) => $q->whereKey($this->operator->district_id));
        }

        return $query->whereRaw('1 = 0');
    }

    public function applyToDistricts(Builder $query): Builder
    {
        if ($this->isNational()) {
            return $query;
        }

        if ($this->operator->isProvincial()) {
            return $query->where('districts.province_id', $this->operator->province_id);
        }

        if ($this->operator->isDistrict()) {
            return $query->whereKey($this->operator->district_id);
        }

        return $query->whereRaw('1 = 0');
    }

    public function applyToGridSubstations(Builder $query): Builder
    {
        if ($this->isNational()) {
            return $query;
        }

        if ($this->operator->isProvincial()) {
            return $query
                ->whereHas('district', fn (Builder $q) => $q->where('province_id', $this->operator->province_id));
        }

        if ($this->operator->isDistrict()) {
            return $query->where('grid_substations.district_id', $this->operator->district_id);
        }

        return $query->whereRaw('1 = 0');
    }

    public function applyToInstallations(Builder $query): Builder
    {
        if ($this->isNational()) {
            return $query;
        }

        if ($this->operator->isProvincial()) {
            return $query
                ->whereHas(
                    'gridSubstation.district',
                    fn (Builder $q) => $q->where('province_id', $this->operator->province_id)
                );
        }

        if ($this->operator->isDistrict()) {
            return $query
                ->whereHas('gridSubstation', fn (Builder $q) => $q->where('district_id', $this->operator->district_id));
        }

        return $query->whereRaw('1 = 0');
    }

    public function allows(Model $model): bool
    {
        if ($this->isNational()) {
            return true;
        }

        return match (true) {
            $model instanceof Province => $this->allowsProvince($model),
            $model instanceof District => $this->allowsDistrict($model),
            $model instanceof GridSubstation => $this->allowsSubstation($model),
            $model instanceof SolarInstallation => $this->allowsInstallation($model),
            $model instanceof GenerationReading => $this->allowsInstallationId((int) $model->solar_installation_id),
            default => false,
        };
    }

    private function allowsProvince(Province $province): bool
    {
        if ($this->operator->isProvincial()) {
            return (int) $province->id === (int) $this->operator->province_id;
        }

        if ($this->operator->isDistrict()) {
            return District::query()
                ->whereKey($this->operator->district_id)
                ->where('province_id', $province->id)
                ->exists();
        }

        return false;
    }

    private function allowsDistrict(District $district): bool
    {
        if ($this->operator->isProvincial()) {
            return (int) $district->province_id === (int) $this->operator->province_id;
        }

        if ($this->operator->isDistrict()) {
            return (int) $district->id === (int) $this->operator->district_id;
        }

        return false;
    }

    private function allowsSubstation(GridSubstation $substation): bool
    {
        if ($this->operator->isProvincial()) {
            return $substation->district !== null
                && (int) $substation->district->province_id === (int) $this->operator->province_id;
        }

        if ($this->operator->isDistrict()) {
            return (int) $substation->district_id === (int) $this->operator->district_id;
        }

        return false;
    }

    private function allowsInstallation(SolarInstallation $installation): bool
    {
        return $this->allowsInstallationId((int) $installation->id);
    }

    private function allowsInstallationId(int $installationId): bool
    {
        $installation = SolarInstallation::query()
            ->with('gridSubstation.district')
            ->find($installationId);

        if ($installation === null) {
            return false;
        }

        if ($this->operator->isProvincial()) {
            return (int) $installation->gridSubstation?->district?->province_id === (int) $this->operator->province_id;
        }

        if ($this->operator->isDistrict()) {
            return (int) $installation->gridSubstation?->district_id === (int) $this->operator->district_id;
        }

        return false;
    }
}
