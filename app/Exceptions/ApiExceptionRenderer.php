<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Converts any exception into the API's consistent error envelope:
 *
 *  {
 *    "error": {
 *      "status": 404,
 *      "code": "not_found",
 *      "message": "...",
 *      "details": {}
 *    }
 *  }
 */
final class ApiExceptionRenderer
{
    public static function render(Throwable $e, Request $request): JsonResponse
    {
        [$status, $code, $message, $details] = self::normalize($e, $request);

        return response()->json([
            'error' => [
                'status' => $status,
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
        ], $status);
    }

    /**
     * @return array{0:int,1:string,2:string,3:mixed}
     */
    private static function normalize(Throwable $e, Request $request): array
    {
        if ($e instanceof ApiException) {
            return [$e->status, $e->errorCode, $e->getMessage(), $e->details];
        }

        if ($e instanceof ValidationException) {
            return [422, 'validation_failed', 'The given data was invalid.', $e->errors()];
        }

        if ($e instanceof AuthenticationException) {
            return [401, 'unauthorized', 'Authentication is required to perform this action.', []];
        }

        if ($e instanceof AuthorizationException) {
            return [403, 'forbidden', 'This action is unauthorized.', []];
        }

        if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
            return [404, 'not_found', 'The requested resource was not found.', []];
        }

        if ($e instanceof MethodNotAllowedHttpException) {
            return [405, 'method_not_allowed', 'The HTTP method is not supported for this resource.', []];
        }

        if ($e instanceof ThrottleRequestsException) {
            return [429, 'too_many_requests', 'Too many requests.', []];
        }

        if ($e instanceof QueryException) {
            if (self::isUniqueConstraintViolation($e)) {
                return [409, 'conflict', 'The resource already exists or conflicts with an existing record.', [
                    'sql_state' => $e->getCode(),
                ]];
            }

            return [500, 'database_error', 'A database error occurred.', []];
        }

        if ($e instanceof HttpExceptionInterface) {
            $status = $e->getStatusCode();

            return [
                $status,
                Str::snake(self::reasonPhrase($status)),
                $e->getMessage() !== '' ? $e->getMessage() : 'HTTP error.',
                [],
            ];
        }

        return [
            500,
            'server_error',
            config('app.debug') ? $e->getMessage() : 'An unexpected error occurred.',
            config('app.debug') ? ['exception' => $e::class] : [],
        ];
    }

    private static function isUniqueConstraintViolation(QueryException $e): bool
    {
        $driverCode = $e->errorInfo[1] ?? null;

        return $e->getCode() === '23000'
            || $driverCode === 1062
            || str_contains($e->getMessage(), 'Duplicate entry');
    }

    private static function reasonPhrase(int $status): string
    {
        return match ($status) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            409 => 'Conflict',
            412 => 'Precondition Failed',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            default => 'HTTP Error',
        };
    }
}
