<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Requests\StoreGenerationReadingRequest;
use App\Http\Resources\GenerationReadingResource;
use App\Models\Device;
use App\Models\GenerationReading;
use App\Models\SolarInstallation;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class GenerationReadingController extends ApiController
{
    public function index(Request $request, SolarInstallation $installation): JsonResponse
    {
        $jurisdiction = $this->jurisdiction($request);
        $this->assertWithinJurisdiction($jurisdiction, $installation);

        $options = $this->options(
            $request,
            ['id', 'recorded_at', 'power_kw', 'energy_kwh', 'voltage'],
            'recorded_at',
            'desc',
        );

        $query = $installation->generationReadings()->getQuery();

        $from = $this->parseBoundary($request->input('recorded_at_from'), 'recorded_at_from');
        $to = $this->parseBoundary($request->input('recorded_at_to'), 'recorded_at_to');

        if ($from !== null) {
            $query->where('recorded_at', '>=', $from);
        }

        if ($to !== null) {
            $query->where('recorded_at', '<=', $to);
        }

        if ($from !== null && $to !== null && $from->greaterThan($to)) {
            throw ApiException::badRequest('`recorded_at_from` must be earlier than or equal to `recorded_at_to`.');
        }

        $this->applySort($query, $options, 'recorded_at');

        return $this->collection(
            $request,
            GenerationReadingResource::collection($this->paginate($query, $options))
        );
    }

    public function show(Request $request, SolarInstallation $installation, GenerationReading $reading): JsonResponse
    {
        $jurisdiction = $this->jurisdiction($request);
        $this->assertWithinJurisdiction($jurisdiction, $installation);

        if ((int) $reading->solar_installation_id !== (int) $installation->id) {
            throw ApiException::notFound('The reading does not belong to the specified installation.');
        }

        return $this->single($request, new GenerationReadingResource($reading), $reading);
    }

    public function store(StoreGenerationReadingRequest $request, SolarInstallation $installation): JsonResponse
    {
        $principal = $request->user('api');

        if (! $principal instanceof Device) {
            throw ApiException::forbidden('Only a device attached to this installation may append readings.');
        }

        if ((int) $principal->solar_installation_id !== (int) $installation->id) {
            throw ApiException::forbidden(
                'The device is not authorised to report readings for this installation.'
            );
        }

        $principal->forceFill(['last_used_at' => now()])->saveQuietly();

        $data = $request->validated();
        $recordedAt = Carbon::parse($data['recorded_at'])->utc();

        $alreadyExists = $installation->generationReadings()
            ->where('recorded_at', $recordedAt)
            ->exists();

        if ($alreadyExists) {
            throw ApiException::conflict('A reading for this installation at the given recorded_at already exists.', [
                'recorded_at' => $recordedAt->toIso8601String(),
            ]);
        }

        try {
            $reading = $installation->generationReadings()->create([
                'recorded_at' => $recordedAt,
                'power_kw' => $data['power_kw'],
                'energy_kwh' => $data['energy_kwh'],
                'voltage' => $data['voltage'],
            ]);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                throw ApiException::conflict('A reading for this installation at the given recorded_at already exists.', [
                    'recorded_at' => $recordedAt->toIso8601String(),
                ]);
            }

            throw $e;
        }

        return $this->single(
            $request,
            new GenerationReadingResource($reading),
            $reading,
            201,
            ['Location' => url('/api/installations/'.$installation->id.'/readings/'.$reading->id)],
        );
    }

    private function parseBoundary(mixed $value, string $name): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->utc();
        } catch (\Throwable) {
            throw ApiException::badRequest("The `{$name}` query parameter must be a valid ISO 8601 date-time.", [
                $name => $value,
            ]);
        }
    }
}
