<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Requests\StoreDistrictRequest;
use App\Http\Requests\UpdateDistrictRequest;
use App\Http\Resources\DistrictResource;
use App\Http\Resources\GridSubstationResource;
use App\Models\District;
use App\Support\GenerationSummaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class DistrictController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $options = $this->options($request, ['id', 'name', 'created_at'], 'name', 'asc');

        $jurisdiction = $this->jurisdiction($request);

        $query = District::query()->with('province')->withCount('gridSubstations');
        $jurisdiction->applyToDistricts($query);

        if ($province = $request->input('province')) {
            $query->whereHas('province', function ($relation) use ($province): void {
                $relation->where(function ($inner) use ($province): void {
                    $inner->where('code', $province);

                    if (ctype_digit((string) $province)) {
                        $inner->orWhere('id', $province);
                    }
                });
            });
        }

        if ($search = $request->input('q')) {
            $query->where('name', 'like', '%'.$search.'%');
        }

        $this->applySort($query, $options, 'name');

        return $this->collection(
            $request,
            DistrictResource::collection($this->paginate($query, $options))
        );
    }

    public function show(Request $request, District $district): JsonResponse
    {
        $jurisdiction = $this->jurisdiction($request);
        $this->assertWithinJurisdiction($jurisdiction, $district);

        $district->load('province')->loadCount('gridSubstations');

        return $this->single($request, new DistrictResource($district), $district);
    }

    public function store(StoreDistrictRequest $request): JsonResponse
    {
        $data = $request->validated();

        $this->assertUnique('districts', 'name', $data['name'], 'district');

        $district = District::create($data);

        return $this->single(
            $request,
            new DistrictResource($district),
            $district,
            201,
            ['Location' => url('/api/districts/'.$district->id)],
        );
    }

    public function update(UpdateDistrictRequest $request, District $district): JsonResponse
    {
        $this->enforcePreconditions($request, $district);

        $district->update($request->validated());

        return $this->single($request, new DistrictResource($district->refresh()), $district);
    }

    public function destroy(Request $request, District $district): Response
    {
        $this->enforcePreconditions($request, $district);

        $district->delete();

        return response()->noContent();
    }

    public function gridSubstations(Request $request, District $district): JsonResponse
    {
        $options = $this->options($request, ['id', 'name', 'code', 'created_at'], 'name', 'asc');

        $jurisdiction = $this->jurisdiction($request);
        $this->assertWithinJurisdiction($jurisdiction, $district);

        $query = $district->gridSubstations()->getQuery()
            ->with('district.province')
            ->withCount('solarInstallations');

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

    public function generationSummary(Request $request, District $district, GenerationSummaryService $service): JsonResponse
    {
        $jurisdiction = $this->jurisdiction($request);
        $this->assertWithinJurisdiction($jurisdiction, $district);

        $district->load('province');

        $asOf = null;

        if ($date = $request->input('date')) {
            try {
                $asOf = Carbon::createFromFormat('!Y-m-d', (string) $date, 'UTC')->endOfDay();
            } catch (\Throwable) {
                throw ApiException::badRequest('The `date` query parameter must be a valid Y-m-d date.', [
                    'date' => $date,
                ]);
            }
        }

        $summary = $service->summarize($district, $asOf);

        $response = response()->json(['data' => $summary]);
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
