<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * OpenAPI 3 (Swagger) definition generated from the live API routes.
 *
 * Fetch the JSON at GET /api/docs and browse the interactive UI at
 * GET /api/documentation.
 */
#[OA\Info(
    version: '1.0.0',
    title: 'SLSEA Solar Generation Data API',
    description: 'Real-time solar generation data service. JWT bearer authentication: '.
        'operators authenticate at /api/auth/operator/login, solar devices at /api/auth/device/login. '.
        'Operators are scoped to their geographic jurisdiction (national, provincial or district); '.
        'devices may only append readings for their own installation.',
)]
#[OA\Server(url: '/', description: 'API server (relative to this deployment)')]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'JWT access token returned by the login endpoints. Format: Authorization: Bearer <token>',
)]
class OpenApiSpec
{
    // ------------------------------------------------------------------
    // Components
    // ------------------------------------------------------------------

    #[OA\Schema(
        schema: 'Error',
        type: 'object',
        properties: [
            new OA\Property(property: 'status', type: 'integer', example: 404),
            new OA\Property(property: 'code', type: 'string', example: 'not_found'),
            new OA\Property(property: 'message', type: 'string'),
            new OA\Property(property: 'details', type: 'object', nullable: true),
        ],
    )]
    public function error() {}

    #[OA\Schema(
        schema: 'PaginationMeta',
        type: 'object',
        properties: [
            new OA\Property(property: 'current_page', type: 'integer'),
            new OA\Property(property: 'from', type: 'integer', nullable: true),
            new OA\Property(property: 'last_page', type: 'integer'),
            new OA\Property(property: 'per_page', type: 'integer'),
            new OA\Property(property: 'to', type: 'integer', nullable: true),
            new OA\Property(property: 'total', type: 'integer'),
        ],
    )]
    public function paginationMeta() {}

    #[OA\Schema(
        schema: 'PaginationLinks',
        type: 'object',
        properties: [
            new OA\Property(property: 'first', type: 'string', nullable: true),
            new OA\Property(property: 'last', type: 'string', nullable: true),
            new OA\Property(property: 'prev', type: 'string', nullable: true),
            new OA\Property(property: 'next', type: 'string', nullable: true),
        ],
    )]
    public function paginationLinks() {}

    #[OA\Schema(
        schema: 'Province',
        type: 'object',
        properties: [
            new OA\Property(property: 'id', type: 'integer', example: 1),
            new OA\Property(property: 'name', type: 'string', example: 'Western'),
            new OA\Property(property: 'code', type: 'string', example: 'WP'),
            new OA\Property(property: 'districts_count', type: 'integer', example: 3, nullable: true),
            new OA\Property(property: 'districts', type: 'array', items: new OA\Items(ref: '#/components/schemas/District'), nullable: true),
            new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
            new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
        ],
    )]
    public function province() {}

    #[OA\Schema(
        schema: 'ProvincePage',
        type: 'object',
        properties: [
            new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Province')),
            new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
            new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
        ],
    )]
    public function provincePage() {}

    #[OA\Schema(
        schema: 'District',
        type: 'object',
        properties: [
            new OA\Property(property: 'id', type: 'integer', example: 1),
            new OA\Property(property: 'province_id', type: 'integer', example: 1),
            new OA\Property(property: 'name', type: 'string', example: 'Colombo'),
            new OA\Property(property: 'province', ref: '#/components/schemas/Province', nullable: true),
            new OA\Property(property: 'grid_substations_count', type: 'integer', example: 2, nullable: true),
            new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
            new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
        ],
    )]
    public function district() {}

    #[OA\Schema(
        schema: 'DistrictPage',
        type: 'object',
        properties: [
            new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/District')),
            new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
            new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
        ],
    )]
    public function districtPage() {}

    #[OA\Schema(
        schema: 'GridSubstation',
        type: 'object',
        properties: [
            new OA\Property(property: 'id', type: 'integer', example: 1),
            new OA\Property(property: 'district_id', type: 'integer', example: 1),
            new OA\Property(property: 'name', type: 'string', example: 'Colombo Fort Substation'),
            new OA\Property(property: 'code', type: 'string', example: 'SUB-COL-01'),
            new OA\Property(property: 'location', type: 'string', nullable: true, example: 'Colombo Fort'),
            new OA\Property(property: 'district', ref: '#/components/schemas/District', nullable: true),
            new OA\Property(property: 'installations_count', type: 'integer', example: 9, nullable: true),
            new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
            new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
        ],
    )]
    public function gridSubstation() {}

    #[OA\Schema(
        schema: 'GridSubstationPage',
        type: 'object',
        properties: [
            new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/GridSubstation')),
            new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
            new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
        ],
    )]
    public function gridSubstationPage() {}

    #[OA\Schema(
        schema: 'SolarInstallation',
        type: 'object',
        properties: [
            new OA\Property(property: 'id', type: 'integer', example: 1),
            new OA\Property(property: 'grid_substation_id', type: 'integer', example: 1),
            new OA\Property(property: 'meter_id', type: 'string', example: 'MTR-000001'),
            new OA\Property(property: 'capacity_kw', type: 'number', format: 'double', example: 3.2),
            new OA\Property(property: 'address', type: 'string', nullable: true),
            new OA\Property(property: 'installed_at', type: 'string', format: 'date', nullable: true),
            new OA\Property(property: 'status', type: 'string', enum: ['active', 'maintenance', 'suspended']),
            new OA\Property(property: 'grid_substation', ref: '#/components/schemas/GridSubstation', nullable: true),
            new OA\Property(property: 'readings_count', type: 'integer', example: 672, nullable: true),
            new OA\Property(property: 'latest_reading', ref: '#/components/schemas/GenerationReading', nullable: true),
            new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
            new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
        ],
    )]
    public function solarInstallation() {}

    #[OA\Schema(
        schema: 'SolarInstallationPage',
        type: 'object',
        properties: [
            new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/SolarInstallation')),
            new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
            new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
        ],
    )]
    public function solarInstallationPage() {}

    #[OA\Schema(
        schema: 'GenerationReading',
        type: 'object',
        properties: [
            new OA\Property(property: 'id', type: 'integer', example: 1001),
            new OA\Property(property: 'solar_installation_id', type: 'integer', example: 1),
            new OA\Property(property: 'recorded_at', type: 'string', format: 'date-time', example: '2026-10-07T23:45:00Z'),
            new OA\Property(property: 'power_kw', type: 'number', format: 'double', example: 3.1),
            new OA\Property(property: 'energy_kwh', type: 'number', format: 'double', example: 204.567),
            new OA\Property(property: 'voltage', type: 'number', format: 'double', example: 230.5),
            new OA\Property(property: 'solar_installation', ref: '#/components/schemas/SolarInstallation', nullable: true),
            new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        ],
    )]
    public function generationReading() {}

    #[OA\Schema(
        schema: 'GenerationReadingPage',
        type: 'object',
        properties: [
            new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/GenerationReading')),
            new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
            new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
        ],
    )]
    public function generationReadingPage() {}

    #[OA\Schema(
        schema: 'GenerationSummary',
        type: 'object',
        properties: [
            new OA\Property(property: 'district', type: 'object', properties: [
                new OA\Property(property: 'id', type: 'integer'),
                new OA\Property(property: 'name', type: 'string'),
            ]),
            new OA\Property(property: 'province', type: 'object', nullable: true, properties: [
                new OA\Property(property: 'id', type: 'integer'),
                new OA\Property(property: 'code', type: 'string'),
                new OA\Property(property: 'name', type: 'string'),
            ]),
            new OA\Property(property: 'utc_day', type: 'string', format: 'date'),
            new OA\Property(property: 'day_start_utc', type: 'string', format: 'date-time'),
            new OA\Property(property: 'day_end_utc', type: 'string', format: 'date-time'),
            new OA\Property(property: 'as_of', type: 'string', format: 'date-time'),
            new OA\Property(property: 'installation_count', type: 'integer'),
            new OA\Property(property: 'current_total_power_kw', type: 'number', format: 'double'),
            new OA\Property(property: 'today_generated_energy_kwh', type: 'number', format: 'double'),
        ],
    )]
    public function generationSummary() {}

    #[OA\Schema(
        schema: 'Principal',
        type: 'object',
        properties: [
            new OA\Property(property: 'type', type: 'string', enum: ['user', 'device']),
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'name', type: 'string'),
            new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true),
            new OA\Property(property: 'role', type: 'string', enum: ['national', 'provincial', 'district', 'administrator'], nullable: true),
            new OA\Property(property: 'scopes', type: 'array', items: new OA\Items(type: 'string')),
            new OA\Property(property: 'province_id', type: 'integer', nullable: true),
            new OA\Property(property: 'district_id', type: 'integer', nullable: true),
            new OA\Property(property: 'solar_installation_id', type: 'integer', nullable: true),
            new OA\Property(property: 'meter_id', type: 'string', nullable: true),
            new OA\Property(property: 'is_active', type: 'boolean'),
        ],
    )]
    public function principal() {}

    #[OA\Schema(
        schema: 'AuthTokenResponse',
        type: 'object',
        properties: [
            new OA\Property(property: 'access_token', type: 'string'),
            new OA\Property(property: 'refresh_token', type: 'string'),
            new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
            new OA\Property(property: 'expires_in', type: 'integer', example: 3600),
            new OA\Property(property: 'principal', ref: '#/components/schemas/Principal'),
        ],
    )]
    public function authTokenResponse() {}

    #[OA\Schema(
        schema: 'StoreProvinceRequest',
        type: 'object',
        required: ['name', 'code'],
        properties: [
            new OA\Property(property: 'name', type: 'string', maxLength: 255),
            new OA\Property(property: 'code', type: 'string', maxLength: 10),
        ],
    )]
    public function storeProvinceRequest() {}

    #[OA\Schema(
        schema: 'StoreDistrictRequest',
        type: 'object',
        required: ['province_id', 'name'],
        properties: [
            new OA\Property(property: 'province_id', type: 'integer'),
            new OA\Property(property: 'name', type: 'string', maxLength: 255),
        ],
    )]
    public function storeDistrictRequest() {}

    #[OA\Schema(
        schema: 'StoreGridSubstationRequest',
        type: 'object',
        required: ['district_id', 'name', 'code'],
        properties: [
            new OA\Property(property: 'district_id', type: 'integer'),
            new OA\Property(property: 'name', type: 'string', maxLength: 255),
            new OA\Property(property: 'code', type: 'string', maxLength: 255),
            new OA\Property(property: 'location', type: 'string', nullable: true),
        ],
    )]
    public function storeGridSubstationRequest() {}

    #[OA\Schema(
        schema: 'StoreSolarInstallationRequest',
        type: 'object',
        required: ['grid_substation_id', 'meter_id', 'capacity_kw'],
        properties: [
            new OA\Property(property: 'grid_substation_id', type: 'integer'),
            new OA\Property(property: 'meter_id', type: 'string', maxLength: 255),
            new OA\Property(property: 'capacity_kw', type: 'number', format: 'double', minimum: 0),
            new OA\Property(property: 'address', type: 'string', nullable: true),
            new OA\Property(property: 'installed_at', type: 'string', format: 'date', nullable: true),
            new OA\Property(property: 'status', type: 'string', enum: ['active', 'maintenance', 'suspended'], default: 'active'),
        ],
    )]
    public function storeSolarInstallationRequest() {}

    #[OA\Schema(
        schema: 'StoreGenerationReadingRequest',
        type: 'object',
        required: ['recorded_at', 'power_kw', 'energy_kwh', 'voltage'],
        properties: [
            new OA\Property(property: 'recorded_at', type: 'string', format: 'date-time', example: '2026-10-08T00:00:00Z'),
            new OA\Property(property: 'power_kw', type: 'number', format: 'double', minimum: 0),
            new OA\Property(property: 'energy_kwh', type: 'number', format: 'double', minimum: 0),
            new OA\Property(property: 'voltage', type: 'number', format: 'double', minimum: 0),
        ],
    )]
    public function storeGenerationReadingRequest() {}

    #[OA\Schema(
        schema: 'OperatorLoginRequest',
        type: 'object',
        required: ['email', 'password'],
        properties: [
            new OA\Property(property: 'email', type: 'string', format: 'email', example: 'national@slsea.gov.lk'),
            new OA\Property(property: 'password', type: 'string', format: 'password'),
        ],
    )]
    public function operatorLoginRequest() {}

    #[OA\Schema(
        schema: 'DeviceLoginRequest',
        type: 'object',
        required: ['device_identifier', 'secret'],
        properties: [
            new OA\Property(property: 'device_identifier', type: 'string', example: 'DEV-...'),
            new OA\Property(property: 'secret', type: 'string', format: 'password'),
        ],
    )]
    public function deviceLoginRequest() {}

    #[OA\Schema(
        schema: 'RefreshTokenRequest',
        type: 'object',
        required: ['refresh_token'],
        properties: [
            new OA\Property(property: 'refresh_token', type: 'string'),
        ],
    )]
    public function refreshTokenRequest() {}

    // ------------------------------------------------------------------
    // Authentication
    // ------------------------------------------------------------------

    #[OA\Post(
        path: '/api/auth/operator/login',
        tags: ['Authentication'],
        operationId: 'operatorLogin',
        summary: 'Issue JWT credentials for an SLSEA operator',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/OperatorLoginRequest'),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Token pair issued', content: new OA\JsonContent(ref: '#/components/schemas/AuthTokenResponse')),
            new OA\Response(response: 401, description: 'Invalid credentials', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'Account not allowed', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'Validation failed', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 429, description: 'Too many attempts', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function operatorLogin() {}

    #[OA\Post(
        path: '/api/auth/device/login',
        tags: ['Authentication'],
        operationId: 'deviceLogin',
        summary: 'Issue JWT credentials for a solar device',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/DeviceLoginRequest'),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Token pair issued', content: new OA\JsonContent(ref: '#/components/schemas/AuthTokenResponse')),
            new OA\Response(response: 401, description: 'Invalid device credentials', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'Validation failed', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 429, description: 'Too many attempts', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function deviceLogin() {}

    #[OA\Post(
        path: '/api/auth/refresh',
        tags: ['Authentication'],
        operationId: 'refreshToken',
        summary: 'Exchange a refresh token for a new token pair',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/RefreshTokenRequest'),
        ),
        responses: [
            new OA\Response(response: 200, description: 'New token pair issued', content: new OA\JsonContent(ref: '#/components/schemas/AuthTokenResponse')),
            new OA\Response(response: 401, description: 'Refresh token invalid or expired', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'Validation failed', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 429, description: 'Too many attempts', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function refreshToken() {}

    #[OA\Get(
        path: '/api/auth/me',
        tags: ['Authentication'],
        operationId: 'me',
        summary: 'Return the current authenticated principal',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Principal details', content: new OA\JsonContent(
                properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Principal')],
                type: 'object',
            )),
            new OA\Response(response: 401, description: 'Missing or invalid token', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function me() {}

    #[OA\Post(
        path: '/api/auth/logout',
        tags: ['Authentication'],
        operationId: 'logout',
        summary: 'Revoke the presented access token',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Token revoked', content: new OA\JsonContent(
                properties: [new OA\Property(property: 'data', type: 'object', properties: [new OA\Property(property: 'revoked', type: 'boolean', example: true)])],
            )),
            new OA\Response(response: 401, description: 'Missing or invalid token', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function logout() {}

    // ------------------------------------------------------------------
    // Provinces
    // ------------------------------------------------------------------

    #[OA\Get(
        path: '/api/provinces',
        tags: ['Provinces'],
        operationId: 'listProvinces',
        summary: 'List provinces (jurisdiction-scoped)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\QueryParameter(name: 'page', schema: new OA\Schema(type: 'integer', minimum: 1), description: 'Page number'),
            new OA\QueryParameter(name: 'per_page', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100), description: 'Items per page'),
            new OA\QueryParameter(name: 'sort', schema: new OA\Schema(type: 'string', enum: ['id', 'name', 'code', 'created_at']), description: 'Sort field'),
            new OA\QueryParameter(name: 'direction', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc']), description: 'Sort direction'),
            new OA\QueryParameter(name: 'q', schema: new OA\Schema(type: 'string'), description: 'Search by name'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated provinces', content: new OA\JsonContent(ref: '#/components/schemas/ProvincePage')),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'Not an operator', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function provincesIndex() {}

    #[OA\Post(
        path: '/api/provinces',
        tags: ['Provinces'],
        operationId: 'storeProvince',
        summary: 'Create a province (administrator only)',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreProvinceRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(
                properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Province')],
                type: 'object',
            )),
            new OA\Response(response: 403, description: 'Not an administrator', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'Name or code conflict', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'Validation failed', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function provincesStore() {}

    #[OA\Get(
        path: '/api/provinces/{province}',
        tags: ['Provinces'],
        operationId: 'showProvince',
        summary: 'Show a province by id or code',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'province', required: true, schema: new OA\Schema(type: 'string'), description: 'Province id or code (e.g. 1 or WP)'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Province', content: new OA\JsonContent(
                properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Province')],
                type: 'object',
            )),
            new OA\Response(response: 404, description: 'Not found or out of jurisdiction', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function provincesShow() {}

    #[OA\Put(
        path: '/api/provinces/{province}',
        tags: ['Provinces'],
        operationId: 'updateProvince',
        summary: 'Update a province (administrator only)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'province', required: true, schema: new OA\Schema(type: 'string'), description: 'Province id or code'),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreProvinceRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Updated', content: new OA\JsonContent(
                properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Province')],
                type: 'object',
            )),
            new OA\Response(response: 403, description: 'Not an administrator', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'Name or code conflict', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 412, description: 'Precondition failed', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'Validation failed', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function provincesUpdate() {}

    #[OA\Delete(
        path: '/api/provinces/{province}',
        tags: ['Provinces'],
        operationId: 'deleteProvince',
        summary: 'Delete a province (administrator only)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'province', required: true, schema: new OA\Schema(type: 'string'), description: 'Province id or code'),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Deleted'),
            new OA\Response(response: 403, description: 'Not an administrator', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'Has dependent districts', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 412, description: 'Precondition failed', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function provincesDestroy() {}

    #[OA\Get(
        path: '/api/provinces/{province}/districts',
        tags: ['Provinces'],
        operationId: 'listProvinceDistricts',
        summary: 'List districts within a province (jurisdiction-scoped)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'province', required: true, schema: new OA\Schema(type: 'string'), description: 'Province id or code'),
            new OA\QueryParameter(name: 'page', schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\QueryParameter(name: 'per_page', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100)),
            new OA\QueryParameter(name: 'sort', schema: new OA\Schema(type: 'string', enum: ['id', 'name', 'created_at'])),
            new OA\QueryParameter(name: 'direction', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
            new OA\QueryParameter(name: 'q', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated districts', content: new OA\JsonContent(ref: '#/components/schemas/DistrictPage')),
            new OA\Response(response: 404, description: 'Province not found or out of jurisdiction', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function provincesDistricts() {}

    // ------------------------------------------------------------------
    // Districts
    // ------------------------------------------------------------------

    #[OA\Get(
        path: '/api/districts',
        tags: ['Districts'],
        operationId: 'listDistricts',
        summary: 'List districts (jurisdiction-scoped)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\QueryParameter(name: 'page', schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\QueryParameter(name: 'per_page', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100)),
            new OA\QueryParameter(name: 'sort', schema: new OA\Schema(type: 'string', enum: ['id', 'name', 'created_at'])),
            new OA\QueryParameter(name: 'direction', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
            new OA\QueryParameter(name: 'q', schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'province', schema: new OA\Schema(type: 'string'), description: 'Province id or code filter'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated districts', content: new OA\JsonContent(ref: '#/components/schemas/DistrictPage')),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'Not an operator', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function districtsIndex() {}

    #[OA\Post(
        path: '/api/districts',
        tags: ['Districts'],
        operationId: 'storeDistrict',
        summary: 'Create a district (administrator only)',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreDistrictRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(
                properties: [new OA\Property(property: 'data', ref: '#/components/schemas/District')],
                type: 'object',
            )),
            new OA\Response(response: 403, description: 'Not an administrator', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'Name conflict', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'Validation failed', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function districtsStore() {}

    #[OA\Get(
        path: '/api/districts/{district}',
        tags: ['Districts'],
        operationId: 'showDistrict',
        summary: 'Show a district by id or name',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'district', required: true, schema: new OA\Schema(type: 'string'), description: 'District id or name (e.g. 1 or Colombo)'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'District', content: new OA\JsonContent(
                properties: [new OA\Property(property: 'data', ref: '#/components/schemas/District')],
                type: 'object',
            )),
            new OA\Response(response: 404, description: 'Not found or out of jurisdiction', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function districtsShow() {}

    #[OA\Put(
        path: '/api/districts/{district}',
        tags: ['Districts'],
        operationId: 'updateDistrict',
        summary: 'Update a district (administrator only)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'district', required: true, schema: new OA\Schema(type: 'string'), description: 'District id or name'),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreDistrictRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Updated', content: new OA\JsonContent(
                properties: [new OA\Property(property: 'data', ref: '#/components/schemas/District')],
                type: 'object',
            )),
            new OA\Response(response: 403, description: 'Not an administrator', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'Name conflict', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 412, description: 'Precondition failed', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'Validation failed', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function districtsUpdate() {}

    #[OA\Delete(
        path: '/api/districts/{district}',
        tags: ['Districts'],
        operationId: 'deleteDistrict',
        summary: 'Delete a district (administrator only)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'district', required: true, schema: new OA\Schema(type: 'string'), description: 'District id or name'),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Deleted'),
            new OA\Response(response: 403, description: 'Not an administrator', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'Has dependent substations', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 412, description: 'Precondition failed', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function districtsDestroy() {}

    #[OA\Get(
        path: '/api/districts/{district}/grid-substations',
        tags: ['Districts'],
        operationId: 'listDistrictSubstations',
        summary: 'List grid substations within a district (jurisdiction-scoped)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'district', required: true, schema: new OA\Schema(type: 'string'), description: 'District id or name'),
            new OA\QueryParameter(name: 'page', schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\QueryParameter(name: 'per_page', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100)),
            new OA\QueryParameter(name: 'sort', schema: new OA\Schema(type: 'string', enum: ['id', 'name', 'code', 'created_at'])),
            new OA\QueryParameter(name: 'direction', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
            new OA\QueryParameter(name: 'q', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated grid substations', content: new OA\JsonContent(ref: '#/components/schemas/GridSubstationPage')),
            new OA\Response(response: 404, description: 'District not found or out of jurisdiction', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function districtSubstations() {}

    #[OA\Get(
        path: '/api/districts/{district}/generation-summary',
        tags: ['Districts'],
        operationId: 'districtSummary',
        summary: 'Generation summary for a district (jurisdiction-scoped)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'district', required: true, schema: new OA\Schema(type: 'string'), description: 'District id or name'),
            new OA\QueryParameter(name: 'date', schema: new OA\Schema(type: 'string', format: 'date'), description: 'UTC day (Y-m-d); defaults to today'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'District generation summary', content: new OA\JsonContent(
                properties: [new OA\Property(property: 'data', ref: '#/components/schemas/GenerationSummary')],
                type: 'object',
            )),
            new OA\Response(response: 400, description: 'Invalid date', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'District not found or out of jurisdiction', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function districtSummary() {}

    // ------------------------------------------------------------------
    // Grid substations
    // ------------------------------------------------------------------

    #[OA\Get(
        path: '/api/grid-substations',
        tags: ['Grid substations'],
        operationId: 'listGridSubstations',
        summary: 'List grid substations (jurisdiction-scoped)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\QueryParameter(name: 'page', schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\QueryParameter(name: 'per_page', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100)),
            new OA\QueryParameter(name: 'sort', schema: new OA\Schema(type: 'string', enum: ['id', 'name', 'code', 'created_at'])),
            new OA\QueryParameter(name: 'direction', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
            new OA\QueryParameter(name: 'q', schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'district', schema: new OA\Schema(type: 'string'), description: 'District id or name filter'),
            new OA\QueryParameter(name: 'province', schema: new OA\Schema(type: 'string'), description: 'Province id or code filter'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated grid substations', content: new OA\JsonContent(ref: '#/components/schemas/GridSubstationPage')),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'Not an operator', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function gridSubstationsIndex() {}

    #[OA\Post(
        path: '/api/grid-substations',
        tags: ['Grid substations'],
        operationId: 'storeGridSubstation',
        summary: 'Create a grid substation (administrator only)',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreGridSubstationRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(
                properties: [new OA\Property(property: 'data', ref: '#/components/schemas/GridSubstation')],
                type: 'object',
            )),
            new OA\Response(response: 403, description: 'Not an administrator', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'Code conflict', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'Validation failed', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function gridSubstationsStore() {}

    #[OA\Get(
        path: '/api/grid-substations/{gridSubstation}',
        tags: ['Grid substations'],
        operationId: 'showGridSubstation',
        summary: 'Show a grid substation by id or code',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'gridSubstation', required: true, schema: new OA\Schema(type: 'string'), description: 'Substation id or code (e.g. SUB-COL-01)'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Grid substation', content: new OA\JsonContent(
                properties: [new OA\Property(property: 'data', ref: '#/components/schemas/GridSubstation')],
                type: 'object',
            )),
            new OA\Response(response: 404, description: 'Not found or out of jurisdiction', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function gridSubstationsShow() {}

    #[OA\Put(
        path: '/api/grid-substations/{gridSubstation}',
        tags: ['Grid substations'],
        operationId: 'updateGridSubstation',
        summary: 'Update a grid substation (administrator only)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'gridSubstation', required: true, schema: new OA\Schema(type: 'string'), description: 'Substation id or code'),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreGridSubstationRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Updated', content: new OA\JsonContent(
                properties: [new OA\Property(property: 'data', ref: '#/components/schemas/GridSubstation')],
                type: 'object',
            )),
            new OA\Response(response: 403, description: 'Not an administrator', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'Code conflict', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 412, description: 'Precondition failed', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'Validation failed', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function gridSubstationsUpdate() {}

    #[OA\Delete(
        path: '/api/grid-substations/{gridSubstation}',
        tags: ['Grid substations'],
        operationId: 'deleteGridSubstation',
        summary: 'Delete a grid substation (administrator only)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'gridSubstation', required: true, schema: new OA\Schema(type: 'string'), description: 'Substation id or code'),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Deleted'),
            new OA\Response(response: 403, description: 'Not an administrator', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'Has dependent installations', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 412, description: 'Precondition failed', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function gridSubstationsDestroy() {}

    #[OA\Get(
        path: '/api/grid-substations/{gridSubstation}/installations',
        tags: ['Grid substations'],
        operationId: 'listSubstationInstallations',
        summary: 'List installations within a grid substation (jurisdiction-scoped)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'gridSubstation', required: true, schema: new OA\Schema(type: 'string'), description: 'Substation id or code'),
            new OA\QueryParameter(name: 'page', schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\QueryParameter(name: 'per_page', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100)),
            new OA\QueryParameter(name: 'sort', schema: new OA\Schema(type: 'string', enum: ['id', 'meter_id', 'capacity_kw', 'installed_at', 'created_at'])),
            new OA\QueryParameter(name: 'direction', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
            new OA\QueryParameter(name: 'status', schema: new OA\Schema(type: 'string', enum: ['active', 'maintenance', 'suspended'])),
            new OA\QueryParameter(name: 'q', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated installations', content: new OA\JsonContent(ref: '#/components/schemas/SolarInstallationPage')),
            new OA\Response(response: 404, description: 'Substation not found or out of jurisdiction', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function substationInstallations() {}

    // ------------------------------------------------------------------
    // Solar installations
    // ------------------------------------------------------------------

    #[OA\Get(
        path: '/api/installations',
        tags: ['Solar installations'],
        operationId: 'listInstallations',
        summary: 'List solar installations (jurisdiction-scoped)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\QueryParameter(name: 'page', schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\QueryParameter(name: 'per_page', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100)),
            new OA\QueryParameter(name: 'sort', schema: new OA\Schema(type: 'string', enum: ['id', 'meter_id', 'capacity_kw', 'installed_at', 'created_at'])),
            new OA\QueryParameter(name: 'direction', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
            new OA\QueryParameter(name: 'q', schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'province', schema: new OA\Schema(type: 'string'), description: 'Province id or code filter'),
            new OA\QueryParameter(name: 'district', schema: new OA\Schema(type: 'string'), description: 'District id or name filter'),
            new OA\QueryParameter(name: 'grid_substation', schema: new OA\Schema(type: 'string'), description: 'Substation id or code filter'),
            new OA\QueryParameter(name: 'status', schema: new OA\Schema(type: 'string', enum: ['active', 'maintenance', 'suspended'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated installations', content: new OA\JsonContent(ref: '#/components/schemas/SolarInstallationPage')),
            new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'Not an operator', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function installationsIndex() {}

    #[OA\Post(
        path: '/api/installations',
        tags: ['Solar installations'],
        operationId: 'storeInstallation',
        summary: 'Create a solar installation (administrator only)',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreSolarInstallationRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(
                properties: [new OA\Property(property: 'data', ref: '#/components/schemas/SolarInstallation')],
                type: 'object',
            )),
            new OA\Response(response: 403, description: 'Not an administrator', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'Meter id conflict', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'Validation failed', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function installationsStore() {}

    #[OA\Get(
        path: '/api/installations/{installation}',
        tags: ['Solar installations'],
        operationId: 'showInstallation',
        summary: 'Show a solar installation by id or meter_id',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'installation', required: true, schema: new OA\Schema(type: 'string'), description: 'Installation id or meter_id (e.g. MTR-000001)'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Installation with latest reading', content: new OA\JsonContent(
                properties: [new OA\Property(property: 'data', ref: '#/components/schemas/SolarInstallation')],
                type: 'object',
            )),
            new OA\Response(response: 404, description: 'Not found or out of jurisdiction', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function installationsShow() {}

    #[OA\Put(
        path: '/api/installations/{installation}',
        tags: ['Solar installations'],
        operationId: 'updateInstallation',
        summary: 'Update a solar installation (administrator only)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'installation', required: true, schema: new OA\Schema(type: 'string'), description: 'Installation id or meter_id'),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreSolarInstallationRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Updated', content: new OA\JsonContent(
                properties: [new OA\Property(property: 'data', ref: '#/components/schemas/SolarInstallation')],
                type: 'object',
            )),
            new OA\Response(response: 403, description: 'Not an administrator', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'Meter id conflict', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 412, description: 'Precondition failed', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'Validation failed', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function installationsUpdate() {}

    #[OA\Delete(
        path: '/api/installations/{installation}',
        tags: ['Solar installations'],
        operationId: 'deleteInstallation',
        summary: 'Delete a solar installation (administrator only)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'installation', required: true, schema: new OA\Schema(type: 'string'), description: 'Installation id or meter_id'),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Deleted'),
            new OA\Response(response: 403, description: 'Not an administrator', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'Has dependent readings', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 412, description: 'Precondition failed', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function installationsDestroy() {}

    #[OA\Get(
        path: '/api/installations/{installation}/latest-reading',
        tags: ['Readings'],
        operationId: 'latestReading',
        summary: 'Latest generation reading for an installation (jurisdiction-scoped)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'installation', required: true, schema: new OA\Schema(type: 'string'), description: 'Installation id or meter_id'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Latest reading', content: new OA\JsonContent(
                properties: [new OA\Property(property: 'data', ref: '#/components/schemas/GenerationReading')],
                type: 'object',
            )),
            new OA\Response(response: 404, description: 'Not found, out of jurisdiction or no readings', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function latestReading() {}

    #[OA\Get(
        path: '/api/installations/{installation}/readings',
        tags: ['Readings'],
        operationId: 'listReadings',
        summary: 'List generation readings for an installation (jurisdiction-scoped)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'installation', required: true, schema: new OA\Schema(type: 'string'), description: 'Installation id or meter_id'),
            new OA\QueryParameter(name: 'page', schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\QueryParameter(name: 'per_page', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100)),
            new OA\QueryParameter(name: 'sort', schema: new OA\Schema(type: 'string', enum: ['id', 'recorded_at', 'power_kw', 'energy_kwh', 'voltage'])),
            new OA\QueryParameter(name: 'direction', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
            new OA\QueryParameter(name: 'recorded_at_from', schema: new OA\Schema(type: 'string', format: 'date-time'), description: 'Inclusive lower boundary'),
            new OA\QueryParameter(name: 'recorded_at_to', schema: new OA\Schema(type: 'string', format: 'date-time'), description: 'Inclusive upper boundary'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated readings', content: new OA\JsonContent(ref: '#/components/schemas/GenerationReadingPage')),
            new OA\Response(response: 404, description: 'Installation not found or out of jurisdiction', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function listReadings() {}

    #[OA\Post(
        path: '/api/installations/{installation}/readings',
        tags: ['Readings'],
        operationId: 'storeReading',
        summary: 'Append a generation reading (device only, own installation)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'installation', required: true, schema: new OA\Schema(type: 'string'), description: 'Installation id or meter_id'),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreGenerationReadingRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(
                properties: [new OA\Property(property: 'data', ref: '#/components/schemas/GenerationReading')],
                type: 'object',
            )),
            new OA\Response(response: 403, description: 'Device not attached to this installation', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'Duplicate recorded_at', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'Validation failed (incl. non-monotonic energy, over capacity)', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function storeReading() {}

    #[OA\Get(
        path: '/api/installations/{installation}/readings/{reading}',
        tags: ['Readings'],
        operationId: 'showReading',
        summary: 'Show a single reading (jurisdiction-scoped)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'installation', required: true, schema: new OA\Schema(type: 'string'), description: 'Installation id or meter_id'),
            new OA\PathParameter(name: 'reading', required: true, schema: new OA\Schema(type: 'integer'), description: 'Reading id'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Reading', content: new OA\JsonContent(
                properties: [new OA\Property(property: 'data', ref: '#/components/schemas/GenerationReading')],
                type: 'object',
            )),
            new OA\Response(response: 404, description: 'Not found or out of jurisdiction', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function showReading() {}
}
