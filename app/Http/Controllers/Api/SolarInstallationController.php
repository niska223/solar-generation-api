<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Requests\StoreSolarInstallationRequest;
use App\Http\Requests\UpdateSolarInstallationRequest;
use App\Http\Resources\GenerationReadingResource;
use App\Http\Resources\SolarInstallationResource;
use App\Models\SolarInstallation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SolarInstallationController extends ApiController
{
    private const STATUSES = ['active', 'maintenance', 'suspended'];

    public function index(Request $request): JsonResponse
    {
        $options = $this->options(
            $request,
            ['id', 'meter_id', 'capacity_kw', 'installed_at', 'created_at'],
            'meter_id',
            'asc',
        );

        $query = SolarInstallation::query()
            ->with('gridSubstation.district.province')
            ->withCount('generationReadings');

        if ($province = $request->input('province')) {
            $query->whereHas('gridSubstation.district.province', function ($relation) use ($province): void {
                $relation->where(function ($inner) use ($province): void {
                    $inner->where('code', $province);

                    if (ctype_digit((string) $province)) {
                        $inner->orWhere('id', $province);
                    }
                });
            });
        }

        if ($district = $request->input('district')) {
            $query->whereHas('gridSubstation.district', function ($relation) use ($district): void {
                $relation->where(function ($inner) use ($district): void {
                    $inner->where('name', $district);

                    if (ctype_digit((string) $district)) {
                        $inner->orWhere('id', $district);
                    }
                });
            });
        }

        if ($substation = $request->input('grid_substation')) {
            $query->whereHas('gridSubstation', function ($relation) use ($substation): void {
                $relation->where(function ($inner) use ($substation): void {
                    $inner->where('code', $substation);

                    if (ctype_digit((string) $substation)) {
                        $inner->orWhere('id', $substation);
                    }
                });
            });
        }

        if ($status = $request->input('status')) {
            if (! in_array($status, self::STATUSES, true)) {
                throw ApiException::badRequest('The `status` query parameter is not supported.', [
                    'status' => $status,
                    'allowed' => self::STATUSES,
                ]);
            }

            $query->where('status', $status);
        }

        if ($search = $request->input('q')) {
            $query->where(function ($inner) use ($search): void {
                $inner->where('meter_id', 'like', '%'.$search.'%')
                    ->orWhere('address', 'like', '%'.$search.'%');
            });
        }

        $this->applySort($query, $options, 'meter_id');

        return $this->collection(
            $request,
            SolarInstallationResource::collection($this->paginate($query, $options))
        );
    }

    public function show(Request $request, SolarInstallation $installation): JsonResponse
    {
        $installation->load('gridSubstation.district.province', 'latestReading')->loadCount('generationReadings');

        return $this->single($request, new SolarInstallationResource($installation), $installation);
    }

    public function store(StoreSolarInstallationRequest $request): JsonResponse
    {
        $data = $request->validated();

        $this->assertUnique('solar_installations', 'meter_id', $data['meter_id'], 'solar installation');

        $installation = SolarInstallation::create($data);

        return $this->single(
            $request,
            new SolarInstallationResource($installation),
            $installation,
            201,
            ['Location' => url('/api/installations/'.$installation->id)],
        );
    }

    public function update(UpdateSolarInstallationRequest $request, SolarInstallation $installation): JsonResponse
    {
        $this->enforcePreconditions($request, $installation);

        $installation->update($request->validated());

        return $this->single($request, new SolarInstallationResource($installation->refresh()), $installation);
    }

    public function destroy(Request $request, SolarInstallation $installation): Response
    {
        $this->enforcePreconditions($request, $installation);

        $installation->delete();

        return response()->noContent();
    }

    public function latestReading(Request $request, SolarInstallation $installation): JsonResponse
    {
        $reading = $installation->latestReading()->first();

        if ($reading === null) {
            throw ApiException::notFound('This installation does not have any generation readings yet.');
        }

        return $this->single($request, new GenerationReadingResource($reading), $reading);
    }
}
