<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Represents an API-level HTTP error that is rendered as a consistent
 * JSON error envelope by the global exception handler.
 */
class ApiException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        public readonly int $status,
        public readonly string $errorCode,
        string $message,
        public readonly array $details = [],
    ) {
        parent::__construct($message);
    }

    /**
     * @param  array<string, mixed>  $details
     */
    public static function badRequest(string $message, array $details = []): self
    {
        return new self(400, 'bad_request', $message, $details);
    }

    public static function unauthorized(string $message = 'Authentication is required to perform this action.'): self
    {
        return new self(401, 'unauthorized', $message);
    }

    public static function forbidden(string $message = 'You are not allowed to perform this action.'): self
    {
        return new self(403, 'forbidden', $message);
    }

    /**
     * @param  array<string, mixed>  $details
     */
    public static function notFound(string $message = 'The requested resource was not found.', array $details = []): self
    {
        return new self(404, 'not_found', $message, $details);
    }

    /**
     * @param  array<string, mixed>  $details
     */
    public static function notAcceptable(string $message = 'The requested representation is not supported. Use Accept: application/json.', array $details = []): self
    {
        return new self(406, 'not_acceptable', $message, $details);
    }

    /**
     * @param  array<string, mixed>  $details
     */
    public static function conflict(string $message, array $details = []): self
    {
        return new self(409, 'conflict', $message, $details);
    }

    /**
     * @param  array<string, mixed>  $details
     */
    public static function preconditionFailed(string $message = 'A precondition given in the request evaluated to false.', array $details = []): self
    {
        return new self(412, 'precondition_failed', $message, $details);
    }
}
