<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Jurisdiction;
use App\Support\QueryOptions;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\DB;

abstract class ApiController extends Controller
{
    /**
     * Resolve the authenticated SLSEA operator, rejecting devices and guests.
     */
    protected function operator(Request $request): User
    {
        $principal = $request->user('api');

        if (! $principal instanceof User) {
            throw ApiException::forbidden('A SLSEA operator account is required to perform this action.');
        }

        return $principal;
    }

    /**
     * Build the jurisdiction guard for the authenticated operator.
     */
    protected function jurisdiction(Request $request): Jurisdiction
    {
        return new Jurisdiction($this->operator($request));
    }

    /**
     * Treat out-of-jurisdiction resources as non-existent so their existence
     * is never disclosed to the caller.
     */
    protected function assertWithinJurisdiction(Jurisdiction $jurisdiction, Model $model): void
    {
        if (! $jurisdiction->allows($model)) {
            throw ApiException::notFound('The requested resource was not found.');
        }
    }

    protected function assertUnique(string $table, string $column, mixed $value, string $label): void
    {
        if (DB::table($table)->where($column, $value)->exists()) {
            throw ApiException::conflict("A {$label} with this {$column} already exists.", [
                $column => $value,
            ]);
        }
    }

    protected function options(
        Request $request,
        array $allowedSorts = [],
        ?string $defaultSort = null,
        string $defaultDirection = 'desc',
    ): QueryOptions {
        return QueryOptions::fromRequest($request, $allowedSorts, $defaultSort, $defaultDirection);
    }

    protected function applySort(Builder $query, QueryOptions $options, string $fallback = 'id'): Builder
    {
        return $query->orderBy($options->sort ?? $fallback, $options->direction);
    }

    protected function paginate(Builder $query, QueryOptions $options): LengthAwarePaginator
    {
        return $query
            ->paginate($options->perPage, ['*'], 'page', $options->page)
            ->withQueryString();
    }

    /**
     * Serialize a single resource, attaching a strong ETag (derived from the
     * row version) and a Last-Modified timestamp so conditional GET works.
     *
     * @param  array<string, string>  $headers
     */
    protected function single(
        Request $request,
        JsonResource $resource,
        Model $model,
        int $status = 200,
        array $headers = [],
    ): JsonResponse {
        $response = $resource->response($request);
        $response->setStatusCode($status);
        $response->setEtag($this->entityTag($model));

        $lastModified = $model->updated_at ?? $model->created_at;

        if ($lastModified !== null) {
            $response->setLastModified($lastModified);
        }

        foreach ($headers as $name => $value) {
            $response->headers->set($name, $value);
        }

        return $response;
    }

    protected function collection(Request $request, ResourceCollection $collection): JsonResponse
    {
        return $collection->response($request);
    }

    protected function entityTag(Model $model): string
    {
        $timestamp = ($model->updated_at ?? $model->created_at)?->getTimestamp() ?? 0;

        return md5($model->getTable().'|'.$model->getKey().'|'.$timestamp);
    }

    /**
     * Enforce RFC 7232 preconditions for mutating requests.
     *
     *  - If-Match: must be "*" or contain the current entity tag.
     *  - If-Unmodified-Since: the resource must not have changed after it.
     */
    protected function enforcePreconditions(Request $request, Model $model): void
    {
        $ifMatch = $request->header('If-Match');

        if ($ifMatch !== null) {
            $tags = array_map(
                static fn (string $tag): string => trim(trim($tag), '"'),
                explode(',', $ifMatch)
            );

            if (! in_array('*', $tags, true) && ! in_array($this->entityTag($model), $tags, true)) {
                throw ApiException::preconditionFailed(
                    'The If-Match header does not match the current state of the resource.',
                    ['etag' => $this->entityTag($model)],
                );
            }
        }

        $ifUnmodifiedSince = $request->header('If-Unmodified-Since');

        if ($ifUnmodifiedSince !== null) {
            $since = strtotime($ifUnmodifiedSince);
            $modifiedAt = ($model->updated_at ?? $model->created_at)?->getTimestamp();

            if ($since !== false && $modifiedAt !== null && $modifiedAt > $since) {
                throw ApiException::preconditionFailed(
                    'The resource has been modified since the If-Unmodified-Since timestamp.',
                );
            }
        }
    }
}
