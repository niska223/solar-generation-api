<?php

namespace App\Support;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;

/**
 * Parses and validates the shared collection query parameters (pagination,
 * sorting). Invalid values produce a 400 Bad Request with details; payload
 * validation errors remain 422 elsewhere.
 */
final class QueryOptions
{
    /**
     * @param  list<string>  $allowedSorts
     */
    public function __construct(
        public readonly int $page,
        public readonly int $perPage,
        public readonly ?string $sort,
        public readonly string $direction,
    ) {}

    /**
     * @param  list<string>  $allowedSorts
     */
    public static function fromRequest(
        Request $request,
        array $allowedSorts = [],
        ?string $defaultSort = null,
        string $defaultDirection = 'desc',
    ): self {
        $pageRaw = $request->input('page', 1);

        if (! is_numeric($pageRaw) || (int) $pageRaw < 1) {
            throw ApiException::badRequest('The `page` query parameter must be a positive integer.', [
                'page' => $request->input('page'),
            ]);
        }

        $defaultPerPage = (int) config('api.pagination.default_per_page', 15);
        $maxPerPage = (int) config('api.pagination.max_per_page', 100);
        $perPageRaw = $request->input('per_page', $defaultPerPage);

        if (! is_numeric($perPageRaw) || (int) $perPageRaw < 1 || (int) $perPageRaw > $maxPerPage) {
            throw ApiException::badRequest("The `per_page` query parameter must be between 1 and {$maxPerPage}.", [
                'per_page' => $request->input('per_page'),
            ]);
        }

        $sort = $request->input('sort');

        if ($sort !== null && (! is_string($sort) || ! in_array($sort, $allowedSorts, true))) {
            throw ApiException::badRequest('The requested sort field is not supported.', [
                'sort' => $sort,
                'allowed' => $allowedSorts,
            ]);
        }

        $direction = strtolower((string) $request->input('direction', $defaultDirection));

        if (! in_array($direction, ['asc', 'desc'], true)) {
            throw ApiException::badRequest('The `direction` query parameter must be either `asc` or `desc`.', [
                'direction' => $request->input('direction'),
            ]);
        }

        return new self(
            page: (int) $pageRaw,
            perPage: (int) $perPageRaw,
            sort: $sort ?? $defaultSort,
            direction: $direction,
        );
    }
}
