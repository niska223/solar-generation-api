<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreProvinceRequest;
use App\Http\Requests\UpdateProvinceRequest;
use App\Http\Resources\DistrictResource;
use App\Http\Resources\ProvinceResource;
use App\Models\Province;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProvinceController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $options = $this->options($request, ['id', 'name', 'code', 'created_at'], 'name', 'asc');

        $jurisdiction = $this->jurisdiction($request);

        $query = Province::query()
            ->withCount(['districts' => fn ($q) => $jurisdiction->applyToDistricts($q)]);
        $jurisdiction->applyToProvinces($query);

        if ($search = $request->input('q')) {
            $query->where('name', 'like', '%'.$search.'%');
        }

        $this->applySort($query, $options, 'name');

        return $this->collection(
            $request,
            ProvinceResource::collection($this->paginate($query, $options))
        );
    }

    public function show(Request $request, Province $province): JsonResponse
    {
        $jurisdiction = $this->jurisdiction($request);
        $this->assertWithinJurisdiction($jurisdiction, $province);

        $province->loadCount(['districts' => fn ($q) => $jurisdiction->applyToDistricts($q)]);

        return $this->single($request, new ProvinceResource($province), $province);
    }

    public function store(StoreProvinceRequest $request): JsonResponse
    {
        $data = $request->validated();

        $this->assertUnique('provinces', 'name', $data['name'], 'province');
        $this->assertUnique('provinces', 'code', $data['code'], 'province');

        $province = Province::create($data);

        return $this->single(
            $request,
            new ProvinceResource($province),
            $province,
            201,
            ['Location' => url('/api/provinces/'.$province->id)],
        );
    }

    public function update(UpdateProvinceRequest $request, Province $province): JsonResponse
    {
        $this->enforcePreconditions($request, $province);

        $province->update($request->validated());

        return $this->single($request, new ProvinceResource($province->refresh()), $province);
    }

    public function destroy(Request $request, Province $province): Response
    {
        $this->enforcePreconditions($request, $province);

        $province->delete();

        return response()->noContent();
    }

    public function districts(Request $request, Province $province): JsonResponse
    {
        $options = $this->options($request, ['id', 'name', 'created_at'], 'name', 'asc');

        $jurisdiction = $this->jurisdiction($request);
        $this->assertWithinJurisdiction($jurisdiction, $province);

        $query = $province->districts()->getQuery()->with('province')->withCount('gridSubstations');
        $jurisdiction->applyToDistricts($query);

        if ($search = $request->input('q')) {
            $query->where('name', 'like', '%'.$search.'%');
        }

        $this->applySort($query, $options, 'name');

        return $this->collection(
            $request,
            DistrictResource::collection($this->paginate($query, $options))
        );
    }
}
