<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreGridSubstationRequest;
use App\Http\Requests\UpdateGridSubstationRequest;
use App\Http\Resources\GridSubstationResource;
use App\Http\Resources\SolarInstallationResource;
use App\Models\GridSubstation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GridSubstationController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $options = $this->options($request, ['id', 'name', 'code', 'created_at'], 'name', 'asc');

        $query = GridSubstation::query()->with('district.province')->withCount('solarInstallations');

        if ($district = $request->input('district')) {
            $query->whereHas('district', function ($relation) use ($district): void {
                $relation->where(function ($inner) use ($district): void {
                    $inner->where('name', $district);

                    if (ctype_digit((string) $district)) {
                        $inner->orWhere('id', $district);
                    }
                });
            });
        }

        if ($province = $request->input('province')) {
            $query->whereHas('district.province', function ($relation) use ($province): void {
                $relation->where(function ($inner) use ($province): void {
                    $inner->where('code', $province);

                    if (ctype_digit((string) $province)) {
                        $inner->orWhere('id', $province);
                    }
                });
            });
        }

        if ($search = $request->input('q')) {
            $query->where(function ($inner) use ($search): void {
                $inner->where('name', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%');
            });
        }

        $this->applySort($query, $options, 'name');

        return $this->collection(
            $request,
            GridSubstationResource::collection($this->paginate($query, $options))
        );
    }

    public function show(Request $request, GridSubstation $gridSubstation): JsonResponse
    {
        $gridSubstation->load('district.province')->loadCount('solarInstallations');

        return $this->single($request, new GridSubstationResource($gridSubstation), $gridSubstation);
    }

    public function store(StoreGridSubstationRequest $request): JsonResponse
    {
        $data = $request->validated();

        $this->assertUnique('grid_substations', 'code', $data['code'], 'grid substation');

        $substation = GridSubstation::create($data);

        return $this->single(
            $request,
            new GridSubstationResource($substation),
            $substation,
            201,
            ['Location' => url('/api/grid-substations/'.$substation->id)],
        );
    }

    public function update(UpdateGridSubstationRequest $request, GridSubstation $gridSubstation): JsonResponse
    {
        $this->enforcePreconditions($request, $gridSubstation);

        $gridSubstation->update($request->validated());

        return $this->single($request, new GridSubstationResource($gridSubstation->refresh()), $gridSubstation);
    }

    public function destroy(Request $request, GridSubstation $gridSubstation): Response
    {
        $this->enforcePreconditions($request, $gridSubstation);

        $gridSubstation->delete();

        return response()->noContent();
    }

    public function installations(Request $request, GridSubstation $gridSubstation): JsonResponse
    {
        $options = $this->options(
            $request,
            ['id', 'meter_id', 'capacity_kw', 'installed_at', 'created_at'],
            'meter_id',
            'asc',
        );

        $query = $gridSubstation->solarInstallations()->getQuery()
            ->with('gridSubstation.district.province')
            ->withCount('generationReadings');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->input('q')) {
            $query->where('meter_id', 'like', '%'.$search.'%');
        }

        $this->applySort($query, $options, 'meter_id');

        return $this->collection(
            $request,
            SolarInstallationResource::collection($this->paginate($query, $options))
        );
    }
}
